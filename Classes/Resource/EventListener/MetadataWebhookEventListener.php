<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Resource\EventListener;

use Ecentral\CantoSaasFal\Resource\Driver\CantoDriver;
use Ecentral\CantoSaasFal\Resource\Event\MetadataWebhookEvent;
use Ecentral\CantoSaasFal\Resource\Metadata\Extractor;
use Ecentral\CantoSaasFal\Utility\CantoUtility;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\StorageRepository;

final class MetadataWebhookEventListener
{
    private Extractor $metadataExtractor;
    private ResourceFactory $resourceFactory;
    private StorageRepository $storageRepository;

    public function __construct(Extractor $metadataExtractor, ResourceFactory $resourceFactory, StorageRepository $storageRepository)
    {
        $this->metadataExtractor = $metadataExtractor;
        $this->resourceFactory = $resourceFactory;
        $this->storageRepository = $storageRepository;
    }

    public function __invoke(MetadataWebhookEvent $event)
    {
        $cantoStorages = $this->storageRepository->findByStorageType(CantoDriver::DRIVER_NAME);
        $identifier = CantoUtility::buildCombinedIdentifier($event->getScheme(), $event->getId());
        $file = null;
        foreach ($cantoStorages as $storage) {
            $file = $this->resourceFactory->getFileObjectByStorageAndIdentifier($storage->getUid(), $identifier);
            if ($file instanceof File) {
                break;
            }
        }

        $metaData = $this->metadataExtractor->extractMetaData($file);
        $file->getMetaData()->add($metaData)->save();
    }
}
