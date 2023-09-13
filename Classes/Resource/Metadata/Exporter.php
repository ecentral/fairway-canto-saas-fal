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
use Fairway\CantoSaasApi\Http\Asset\BatchUpdatePropertiesRequest;
use Fairway\CantoSaasApi\Http\InvalidResponseException;
use Fairway\CantoSaasFal\Resource\Event\BeforeMetadataUploadEvent;
use Fairway\CantoSaasFal\Resource\Repository\CantoRepository;
use Fairway\CantoSaasFal\Utility\CantoUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileRepository;

final class Exporter
{
    private ConnectionPool $connection;
    private CantoRepository $cantoRepository;
    protected FileRepository $fileRepository;
    private EventDispatcher $dispatcher;

    public function __construct(ConnectionPool $connection, CantoRepository $cantoRepository, FileRepository $fileRepository, EventDispatcher $dispatcher)
    {
        $this->connection = $connection;
        $this->cantoRepository = $cantoRepository;
        $this->fileRepository = $fileRepository;
        $this->dispatcher = $dispatcher;
    }

    public function exportToCanto(int $uid, array $metadata)
    {
        $result = $this->connection->getQueryBuilderForTable('sys_file_metadata')
            ->select('file')
            ->from('sys_file_metadata')
            ->where('uid = ' . $uid)
            ->execute();

        if (method_exists($result, 'fetchAssociative')) {
            $fileUid = $result->fetchAssociative()['file'] ?? null;
        } elseif (method_exists($result, 'fetch')) {
            $fileUid = $result->fetch(FetchMode::ASSOCIATIVE)['file'] ?? null;
        } else {
            return false;
        }

        if ($fileUid === null) {
            return false;
        }
        try {
            $file = $this->fileRepository->findByUid($fileUid);
        } catch (\Exception $e) {
            return false;
        }
        $driveType = $file->getStorage()->getDriverType();
        if (empty($configuration['metadataExportMapping'] ?? [])
            || $driveType != 'Canto') {
            return false;
        }

        assert($file instanceof File);
        ['scheme' => $scheme, 'identifier' => $identifier] = CantoUtility::splitCombinedIdentifier($file->getIdentifier());
        $configuration = $file->getStorage()->getConfiguration();

        $mapping = json_decode($configuration['metadataExportMapping'], true);
        $language = (int)$metadata['sys_language_uid'];
        $this->transformMetadataAndMappingArray($metadata, $mapping, $language);
        $this->cantoRepository->initialize($file->getStorage()->getUid(), $configuration);
        $properties = $this->transformMetadataIntoPropertiesArray($metadata, $mapping, $language);
        $event = $this->dispatcher->dispatch(new BeforeMetadataUploadEvent($scheme, $identifier, $properties));
        assert($event instanceof BeforeMetadataUploadEvent);
        $cantoRequest = new BatchUpdatePropertiesRequest();
        $cantoRequest->addAsset($event->getIdentifier(), $event->getScheme());
        foreach ($event->getProperties() as $property) {
            $cantoRequest->addProperty(
                $property['propertyId'],
                $property['propertyValue'],
                $property['action'],
                $property['customField'],
            );
        }
        try {
            $response = $this->cantoRepository->getClient()->asset()->batchUpdateProperties($cantoRequest);
            if ($response->isSuccessful()) {
                return true;
            }
        } catch (InvalidResponseException $exception) {
            // replace with logger
            debug($exception->getPrevious()->getMessage());
        }
        return false;
    }

    /**
     * This adds support for the data's sys_language_uid
     * Removes all mapping fields not required in the current language
     */
    private function transformMetadataAndMappingArray(array &$metadata, array &$mapping, int $language): void
    {
        $mappingWithLanguageUid = [];
        foreach ($mapping as $key => $value) {
            if ($language === 0 && !str_contains($key, ':')) {
                $mappingWithLanguageUid['0:' . $key] = $value;
            }
            if (!str_starts_with($key, $language . ':')) {
                continue;
            }
            $mappingWithLanguageUid[$key] = $value;
        }
        $mapping = $mappingWithLanguageUid;

        $metadataWithLanguage = [];
        foreach ($metadata as $metadataKey => $metadataValue) {
            $metadataWithLanguage[$language . ':' . $metadataKey] = $metadataValue;
        }
        $metadata = $metadataWithLanguage;
    }

    private function transformMetadataIntoPropertiesArray(array $data, array $mapping, int $language): array
    {
        $properties = [];
        foreach ($mapping as $metadataKey => $cantoField) {
            if (is_string($cantoField)) {
                $cantoField = ['name' => $cantoField];
            }
            if (is_array($cantoField) && isset($cantoField['name'])) {
                $properties[] = [
                    'propertyId' => $cantoField['name'],
                    'propertyValue' => $data[$metadataKey] ?? '',
                    'action' => $cantoField['action'] ?? '',
                    'customField' => $cantoField['customField'] ?? false
                ];
            }
        }
        return $properties;
    }
}
