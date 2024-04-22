<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\CantoSaasFal\Resource\Metadata;

use Fairway\CantoSaasApi\Endpoint\Authorization\AuthorizationFailedException;
use Fairway\CantoSaasFal\Resource\Driver\CantoDriver;
use Fairway\CantoSaasFal\Resource\Event\AfterMetaDataExtractionEvent;
use Fairway\CantoSaasFal\Resource\Repository\CantoRepository;
use Fairway\CantoSaasFal\Utility\CantoUtility;
use JsonException;
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
        $fileData = $this->fetchDataForFile($file);
        if ($fileData === null) {
            return $previousExtractedData;
        }

        $mappedMetaData = $this->getMappedMetaData($file);

        $metadata = array_replace($previousExtractedData, ($mappedMetaData[0] ?? []));
        $event = new AfterMetaDataExtractionEvent($metadata, $fileData);

        return $this->dispatcher->dispatch($event)->getMetadata();
    }
    public function getMappedCategories(File $file): array
    {
        $fileData = $this->fetchDataForFile($file);

        if ($fileData === null) {
            return [];
        }

        $categories = [];
        $configuration = $file->getStorage()->getConfiguration();
        if (array_key_exists('categoryMapping', $configuration)) {
            try {
                $mapping = json_decode($configuration['categoryMapping'], true, 512, JSON_THROW_ON_ERROR);
                // we currently do not support translating sys_categories
                $categoryData = $this->applyMappedMetaData($mapping, [], $fileData)[0] ?? [];
                $categories = $this->buildCategoryTree($categoryData);
            } catch (JsonException $exception) {
                # todo: add mapping logging
            }
        }

        return $categories;
    }

    public function getMappedMetaData(File $file): array
    {
        $fileData = $this->fetchDataForFile($file);

        if ($fileData === null) {
            return [];
        }

        $metadata = [];
        $configuration = $file->getStorage()->getConfiguration();
        if (array_key_exists('metadataMapping', $configuration)) {
            try {
                $mapping = json_decode($configuration['metadataMapping'], true, 512, JSON_THROW_ON_ERROR);
                $metadata = $this->applyMappedMetaData($mapping, $fileData);
            } catch (JsonException $exception) {
                # todo: add mapping logging
            }
            //$metadataObjects[] = $metadata['uid'];
        }
        $array_filedata = [
            'width' => (int)($fileData['width'] ?? 0),
            'height' => (int)($fileData['height'] ?? 0),
            'creator' => $fileData['default']['Author'],
            'copyright' => $fileData['default']['Copyright'],
        ];
        if (isset($fileData['default']['Creation Tool'])) {
            $array_filedata['creator_tool'] = $fileData['default']['Creation Tool'];
        }
        if (isset($fileData['default']['Pages'])) {
            $array_filedata['pages'] = $fileData['default']['Pages'];
        }
        return array_replace(
            [
                $array_filedata
            ],
            $metadata
        );
    }

    public function fetchDataForFile(File $file): ?array
    {
        $configuration = $file->getStorage()->getConfiguration();
        $this->cantoRepository->initialize($file->getStorage()->getUid(), $configuration);

        $scheme = CantoUtility::getSchemeFromCombinedIdentifier($file->getIdentifier());
        $identifier = CantoUtility::getIdFromCombinedIdentifier($file->getIdentifier());
        return $this->cantoRepository->getFileDetails($scheme, $identifier);
    }

    private function applyMappedMetaData(array $mapping, array $fileData): array
    {
        $metadata = [];

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
            if (isset($categoryConfiguration['field'])) {
                [$collection, $customField] = explode('->', $categoryConfiguration['field']);
                if (isset($fileData[$collection])) {
                    if (isset($fileData[$collection][$customField])) {
                        $data = $fileData[$collection][$customField] ?? null;
                        if (isset($categoryConfiguration['alternative'])) {
                            [$collectionAlternative, $customFieldAlternative] = explode('->', $categoryConfiguration['alternative'] ?? '');
                            $data = $fileData[$collection][$customField] ?? $fileData[$collectionAlternative][$customFieldAlternative] ?? null;
                        }
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
     * @param array $mappedCategoryConfiguration
     * @param Category|null $parent
     * @param CategoryRepository|null $categoryRepository
     * @return Category[]
     */
    private function buildCategoryTree(array $mappedCategoryConfiguration, Category $parent = null, CategoryRepository $categoryRepository = null): array
    {
        $categoryRepository ??= GeneralUtility::makeInstance(CategoryRepository::class);
        assert($categoryRepository instanceof CategoryRepository);
        $categories = [];
        foreach ($mappedCategoryConfiguration as $title => $children) {
            $category = $parent;
            if (is_string($title)) {
                $category = $this->addCategory($title, $parent, $categoryRepository);
                $categories[] = $category;
            }
            if (is_array($children) && $category !== null) {
                $categories = [...$categories, ...$this->buildCategoryTree($children, $category, $categoryRepository)];
            }
            if (is_string($children)) {
                $categories[] = $this->addCategory($children, $category, $categoryRepository);
            }
        }
        return $categories;
    }

    private function addCategory(string $title, ?Category $parent, CategoryRepository $repository): Category
    {
        assert(is_callable([$repository, 'findByTitle']));
        $category = $repository->findByTitle($title)->toArray()[0] ?? null;
        if ($category === null) {
            $category = new Category();
            $category->setDescription('Canto generated category');
            $category->setTitle($title);
            if ($parent) {
                $category->setParent($parent);
            }
            $repository->add($category);
        }
        return $category;
    }

    private function getQueryBuilder(string $forTable): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($forTable)
            ->createQueryBuilder();
    }
}
