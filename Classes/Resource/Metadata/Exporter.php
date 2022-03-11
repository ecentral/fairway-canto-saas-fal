<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Resource\Metadata;

use Doctrine\DBAL\FetchMode;
use Ecentral\CantoSaasFal\Resource\Event\BeforeMetadataUploadEvent;
use Ecentral\CantoSaasFal\Resource\Repository\CantoRepository;
use Ecentral\CantoSaasFal\Utility\CantoUtility;
use Fairway\CantoSaasApi\Http\Asset\BatchUpdatePropertiesRequest;
use Fairway\CantoSaasApi\Http\InvalidResponseException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileRepository;

final class Exporter
{
    private ConnectionPool $connection;
    private CantoRepository $cantoRepository;
    private FileRepository $fileRepository;
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
        } else {
            $fileUid = $result->fetch(FetchMode::ASSOCIATIVE)['file'] ?? null;
        }

        if ($fileUid === null) {
            return false;
        }
        try {
            $file = $this->fileRepository->findByUid($fileUid);
        } catch (\Exception $e) {
            return false;
        }
        assert($file instanceof File);
        ['scheme' => $scheme, 'identifier' => $identifier] = CantoUtility::splitCombinedIdentifier($file->getIdentifier());
        $configuration = $file->getStorage()->getConfiguration();
        if (empty($configuration['metadataExportMapping'] ?? [])) {
            return false;
        }
        $mapping = json_decode($configuration['metadataExportMapping'], true);
        $this->cantoRepository->initialize($file->getStorage()->getUid(), $configuration);
        $properties = $this->transformMetadataIntoPropertiesArray($metadata, $mapping);
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

    private function transformMetadataIntoPropertiesArray(array $data, array $mapping): array
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
