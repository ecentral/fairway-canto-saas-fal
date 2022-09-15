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
use Fairway\CantoSaasFal\Utility\CantoUtility;
use TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ForceJpgPreviewImage implements SingletonInterface
{
    public function __invoke(BeforeFileProcessingEvent $event): void
    {
        $file = $event->getFile();
        if ($file instanceof File
            && $event->getTaskType() === ProcessedFile::CONTEXT_IMAGEPREVIEW
            && $event->getDriver() instanceof CantoDriver
            && !CantoUtility::isMdcActivated($event->getFile()->getStorage()->getConfiguration())
        ) {
            $configuration = array_replace(
                $event->getConfiguration(),
                [
                    'fileExtension' => 'jpg',
                ]
            );
            $processedFile = $this->getProcessedFileRepository()
                ->findOneByOriginalFileAndTaskTypeAndConfiguration($file, $event->getTaskType(), $configuration);
            $event->setProcessedFile($processedFile);
        }
    }

    protected function getProcessedFileRepository(): ProcessedFileRepository
    {
        return GeneralUtility::makeInstance(ProcessedFileRepository::class);
    }
}
