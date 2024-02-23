<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\CantoSaasFal\Resource\EventListener;

use Fairway\CantoSaasFal\Resource\Metadata\MetadataRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Event\AfterFileMetaDataCreatedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileMetaDataUpdatedEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class SyncMetaDataTranslationsEventListener
{
    private const TABLE_NAME = 'sys_file_metadata';

    protected Extractor $extractor;

    protected MetadataRepository $metadataRepository;

    protected ResourceFactory $resourceFactory;

    public function __construct(Extractor $extractor, MetadataRepository $metadataRepository, ResourceFactory $resourceFactory)
    {
        $this->extractor = $extractor;
        $this->metadataRepository = $metadataRepository;
        $this->resourceFactory = $resourceFactory;
    }

    public function afterFileMetaDataCreated(AfterFileMetaDataCreatedEvent $event): void
    {
        $this->synchronizeMetaData($event->getFileUid(), $event->getMetaDataUid());
    }

    /**
     * @param AfterFileMetaDataUpdatedEvent $event
     */
    public function afterFileMetaDataUpdated(AfterFileMetaDataUpdatedEvent $event): void
    {
        $this->synchronizeMetaData($event->getFileUid(), $event->getMetaDataUid());
    }

    protected function synchronizeMetaData(int $fileUid, int $metaDataUid): void
    {
        $file = $this->resourceFactory->getFileObject($fileUid);
        if (!$file instanceof File) {
            return;
        }

        $mappedMetadata = $this->extractor->getMappedMetaData($file);

        // Synchronize all available translations
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE_NAME);
        $connection->update(
            self::TABLE_NAME,
            $mappedMetadata[0],
            [
                'file' => $fileUid,
                'l10n_parent' => $metaDataUid,
            ]
        );

        // Update configured languages with their own meta data
        foreach ($mappedMetadata as $languageUid => $metadataArray) {
            if ($languageUid === 0) {
                continue;
            }

            $metadata = $this->metadataRepository->findByFileUidAndLanguageUid($fileUid, (int)$languageUid);

            // Update translated meta data records
            if (!empty($metadata['uid'])) {
                $connection->update(
                    self::TABLE_NAME,
                    $metadataArray,
                    [
                        'file' => $fileUid,
                        'l10n_parent' => $metaDataUid,
                        'sys_language_uid' => $languageUid,
                    ]
                );
            } else {
                $metadata = $this->metadataRepository->findByFileUidAndLanguageUid($fileUid, 0);
                unset($metadata['uid']);
                $connection->insert(
                    self::TABLE_NAME,
                    array_merge(
                        $metadata,
                        $metadataArray,
                        [
                            'file' => $fileUid,
                            'l10n_parent' => $metaDataUid,
                            'sys_language_uid' => $languageUid,
                        ]
                    )
                );
            }
        }
    }
}
