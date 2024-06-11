<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\CantoSaasFal\Resource\EventListener;

use Fairway\CantoSaasFal\Resource\Driver\CantoDriver;
use Fairway\CantoSaasFal\Resource\Metadata\Extractor;
use Fairway\CantoSaasFal\Resource\Metadata\MetadataRepository;
use TYPO3\CMS\Core\Category\Collection\CategoryCollection;
use TYPO3\CMS\Core\Resource\Event\AfterFileMetaDataCreatedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileMetaDataUpdatedEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;

final class SyncMetaDataCategoriesEventListener
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
        $this->synchronizeCategories($event->getFileUid(), $event->getMetaDataUid());
    }

    /**
     * @param AfterFileMetaDataUpdatedEvent $event
     */
    public function afterFileMetaDataUpdated(AfterFileMetaDataUpdatedEvent $event): void
    {
        $this->synchronizeCategories($event->getFileUid(), $event->getMetaDataUid());
    }

    protected function synchronizeCategories(int $fileUid, int $metaDataUid): void
    {
        $file = $this->resourceFactory->getFileObject($fileUid);
        if (!$file instanceof File || $file->getStorage()->getDriverType() != CantoDriver::DRIVER_NAME) {
            return;
        }

        $categories = $this->extractor->getMappedCategories($file);

        $metadata = $this->metadataRepository->findAllByFileUid($fileUid);
        if (!empty($metadata)) {
            foreach ($categories as $category) {
                $categoryCollection = CategoryCollection::load($category->getUid(), true, self::TABLE_NAME, 'categories');
                assert($categoryCollection instanceof CategoryCollection);
                foreach ($metadata as $record) {
                    $categoryCollection->add(['uid' => $record['uid']]);
                }
                $categoryCollection->persist();
            }
        }
    }
}
