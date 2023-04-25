<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\CantoSaasFal\Resource\Metadata;

use Doctrine\DBAL\FetchMode;
use Fairway\CantoSaasApi\Endpoint\Authorization\AuthorizationFailedException;
use Fairway\CantoSaasFal\Resource\Driver\CantoDriver;
use Fairway\CantoSaasFal\Resource\Event\AfterMetaDataExtractionEvent;
use Fairway\CantoSaasFal\Resource\Metadata\MetadataRepository as CantoMetadataRepository;
use Fairway\CantoSaasFal\Resource\Repository\CantoRepository;
use Fairway\CantoSaasFal\Utility\CantoUtility;
use JsonException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\ExtractorInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Extractor implements ExtractorInterface
{
    protected CantoRepository $cantoRepository;
    private EventDispatcher $dispatcher;

    public function __construct(CantoRepository $cantoRepository, EventDispatcher $dispatcher)
    {
        $this->cantoRepository = $cantoRepository;
        $this->dispatcher = $dispatcher;
    }

    public function getFileTypeRestrictions(): array
    {
        return [];
    }

    public function getDriverRestrictions(): array
    {
        return [];
    }

    public function getPriority(): int
    {
        return 10;
    }

    public function getExecutionPriority(): int
    {
        return 10;
    }

    public function canProcess(File $file): bool
    {
        return $file->getStorage()->getDriverType() === CantoDriver::DRIVER_NAME;
    }

    /**
     * @throws AuthorizationFailedException
     */
    public function extractMetaData(File $file, array $previousExtractedData = []): array
    {
        $configuration = $file->getStorage()->getConfiguration();
        $this->cantoRepository->initialize($file->getStorage()->getUid(), $configuration);
        $fileData = $this->fetchDataForFile($file);
        if ($fileData === null) {
            return $previousExtractedData;
        }
        $metadata = array_replace(
            $previousExtractedData,
            [
                'width' => (int)($fileData['width'] ?? 0),
                'height' => (int)($fileData['height'] ?? 0),
                'pages' => (int)($fileData['default']['Pages'] ?? 0),
                'creator' => $fileData['default']['Author'] ?? '',
                'creator_tool' => $fileData['default']['Creation Tool'] ?? '',
                'copyright' => $fileData['default']['Copyright'] ?? '',
            ]
        );
        $mappedMetadata = [];
        $mapping = [];
        if (array_key_exists('metadataMapping', $configuration)) {
            try {
                $mapping = json_decode($configuration['metadataMapping'], true, 512, JSON_THROW_ON_ERROR);
                $mappedMetadata = $this->applyMappedMetaData($mapping, [], $fileData);
            } catch (JsonException $exception) {
                # todo: add mapping logging
            }
        }

        $metadataObjects = [];
        $metadataRepository = GeneralUtility::makeInstance(CantoMetadataRepository::class);
        assert($metadataRepository instanceof CantoMetadataRepository);
        $metadataParentUid = null;
        $parentMetadataArray = $metadataRepository->findByFileUid($file->getUid());
        if (isset($parentMetadataArray['uid'])) {
            $metadataParentUid = $parentMetadataArray['uid'];
        }
        foreach ($mappedMetadata as $languageUid => $metadataArray) {
            $languageUid = (int)$languageUid;
            $result = $metadataRepository->findByFileUidAndLanguageUid($file->getUid(), $languageUid);
            $updatedUid = null;
            if ($result) {
                $result = array_replace($result, $metadataArray);
                $metadataRepository->updateByFileUidAndLanguageUid($file->getUid(), $languageUid, $result);
                $updatedUid = $result['uid'];
            } elseif (empty($result) && $languageUid === 0 && $metadataParentUid === null) {
                $metadataParentUid = $metadataRepository->createMetaDataRecord($file->getUid(), $metadataArray)['uid'];
                $updatedUid = $metadataParentUid;
            } elseif (empty($result) && $languageUid !== 0 && $metadataParentUid !== null) {
                $result = $metadataRepository->createMetaDataRecord($file->getUid(), array_merge(
                    [
                        'sys_language_uid' => $languageUid,
                        'l10n_parent' => $metadataParentUid,
                    ],
                    $metadataArray
                ));
                $updatedUid = $result['uid'];
            }
            $metadataObjects[$languageUid] = $updatedUid;
        }

        if (array_key_exists('categoryMapping', $configuration)) {
            try {
                $mapping = json_decode($configuration['categoryMapping'], true, 512, JSON_THROW_ON_ERROR);
                $this->saveMetadataCategories(
                    $metadataObjects,
                    $this->applyMappedCategoryData($mapping, $fileData)
                );
            } catch (JsonException $exception) {
                # todo: add mapping logging
            }
        }

        $metadata = array_merge($metadata, $mappedMetadata[0] ?? []);
        $event = new AfterMetaDataExtractionEvent($metadata, $mapping, $fileData);
        return $this->dispatcher->dispatch($event)->getMetadata();
    }

    protected function fetchDataForFile(File $file): ?array
    {
        $scheme = CantoUtility::getSchemeFromCombinedIdentifier($file->getIdentifier());
        $identifier = CantoUtility::getIdFromCombinedIdentifier($file->getIdentifier());
        return $this->cantoRepository->getFileDetails($scheme, $identifier);
    }

    private function applyMappedMetaData(array $mapping, array $metadata, array $fileData): array
    {
        foreach ($mapping as $metadataKey => $fileDataKey) {
            if (is_string($fileDataKey)) {
                $fileDataKey = [0 => $fileDataKey];
            }
            foreach ($fileDataKey as $languageUid => $key) {
                $fileDataKeyArray = explode('->', $key);
                $value = $this->extractFromMetadata($fileData, $fileDataKeyArray);
                if ($value) {
                    $metadata[(int)$languageUid][$metadataKey] = $value;
                }
            }
        }

        return $metadata;
    }

    private function applyMappedCategoryData(array $mapping, array $fileData): array
    {
        $uidList = [];
        foreach ($mapping as $categoryUid => $categoryConfiguration) {
            [$collection, $customField] = explode('->', $categoryConfiguration['field']);
            if (isset($fileData[$collection])) {
                if (isset($fileData[$collection][$customField])) {
                    $data = $fileData[$collection][$customField];
                    if ($data === null) {
                        continue;
                    }
                    $flippedMapping = array_flip($categoryConfiguration['mapping']);
                    $uidList[] = $categoryUid;
                    /**
                     * Simple replace mechanism for typo3 category uid duplicates
                     * JSON has unique keys in objects, so we prefix the uids in case we need duplicates:
                     * Example:
                     * {
                     *   "mapping": {
                     *     "23": "category 1",
                     *     "_23": "category 2",
                     *     "__23": "category 2",
                     * }
                     */
                    foreach ($data as $customFieldValue) {
                        if (isset($flippedMapping[$customFieldValue])) {
                            $uidList[] = str_replace('_', '', (string)$flippedMapping[$customFieldValue]);
                        }
                    }
                }
            }
        }

        return array_map(static fn ($uid) => (int)$uid, $uidList);
    }

    private function extractFromMetadata(array $metadata, array $fileKey)
    {
        if (!$metadata || !$fileKey) {
            return null;
        }
        $key = array_shift($fileKey);
        if (count($fileKey) === 0) {
            if ($metadata[$key] ?? null) {
                return $metadata[$key];
            }
            foreach (['metadata', 'additional', 'default'] as $metadataElement) {
                $value = $metadata[$metadataElement][$key] ?? null;
                if ($value) {
                    return $value;
                }
            }
        }
        return $this->extractFromMetadata($metadata[$key] ?? [], $fileKey);
    }

    /**
     * @param array<string|int> $metadataUids
     * @param int[] $categoryUids
     */
    private function saveMetadataCategories(array $metadataUids, array $categoryUids): void
    {
        foreach ($metadataUids as $languageUid => $metadataUid) {
            $qb = $this->getQueryBuilder('sys_category_record_mm');
            $result = $qb->select('uid_local', 'uid_foreign')
                ->from('sys_category_record_mm')
                ->where($qb->expr()->eq('uid_foreign', $metadataUid))
                ->execute();
            $data = [];
            if (method_exists($result, 'fetchAll')) {
                $data = $result->fetchAll(FetchMode::ASSOCIATIVE);
            } elseif (method_exists($result, 'fetchAllAssociative')) {
                $data = $result->fetchAllAssociative();
            }
            $list = array_map(
                static fn (array $item) => (int)$item['uid_local'],
                $data
            );
            $processedCategoryUids = [];
            foreach ($categoryUids as $categoryUid) {
                $categoryUidForMetadata = $categoryUid;
                if ($languageUid !== 0) {
                    $sysCategoryQB = $this->getQueryBuilder('sys_category');
                    $translatedResult = $sysCategoryQB->select('uid')
                        ->from('sys_category')
                        ->where($sysCategoryQB->expr()->eq('l10n_parent', $categoryUid))
                        ->andWhere($sysCategoryQB->expr()->eq('sys_language_uid', $languageUid))
                        ->execute()
                    ;
                    $translatedData = [];
                    if (method_exists($translatedResult, 'fetchAllAssociative')) {
                        $translatedData = $translatedResult->fetchAllAssociative();
                    } elseif (method_exists($translatedResult, 'fetchAll')) {
                        $translatedData = $translatedResult->fetchAll(FetchMode::ASSOCIATIVE);
                    }
                    if (!empty($translatedData)) {
                        $categoryUidForMetadata = (int)$translatedData[0]['uid'];
                    }
                }
                if ($categoryUidForMetadata === 0) {
                    continue;
                }
                $processedCategoryUids[] = $categoryUidForMetadata;
                if (in_array($categoryUidForMetadata, $list, true)) {
                    continue;
                }
                $qb
                    ->insert('sys_category_record_mm')
                    ->values([
                        'uid_local' => $categoryUidForMetadata,
                        'uid_foreign' => $metadataUid,
                        'tablenames' => 'sys_file_metadata',
                        'fieldname' => 'categories',
                        'sorting' => 0,
                        'sorting_foreign' => 0,
                    ])
                    ->execute()
                ;
            }
            $diff = array_diff($list, $processedCategoryUids);
            if (!empty($diff)) {
                $qb->delete('sys_category_record_mm')
                    ->where($qb->expr()->eq('uid_foreign', $metadataUid))
                    ->andWhere($qb->expr()->in('uid_local', $diff))
                    ->andWhere($qb->expr()->eq('tablenames', $qb->quote('sys_file_metadata')))
                    ->andWhere($qb->expr()->eq('fieldname', $qb->quote('categories')))
                    ->execute()
                ;
            }
        }
    }

    private function getQueryBuilder(string $forTable): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($forTable)
            ->createQueryBuilder();
    }
}
