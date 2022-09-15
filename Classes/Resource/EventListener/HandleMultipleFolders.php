<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\CantoSaasFal\Resource\EventListener;

use Fairway\CantoSaasFal\Resource\Repository\CantoAlbumRepository;
use TYPO3\CMS\Core\Resource\Event\AfterFileAddedToIndexEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileUpdatedInIndexEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\SingletonInterface;

class HandleMultipleFolders implements SingletonInterface
{
    protected FileRepository $fileRepository;

    protected CantoAlbumRepository $albumRepository;

    public function __construct(FileRepository $fileRepository, CantoAlbumRepository $albumRepository)
    {
        $this->fileRepository = $fileRepository;
        $this->albumRepository = $albumRepository;
    }

    public function afterFileAddedToIndexEvent(AfterFileAddedToIndexEvent $event): void
    {
        /** @var File $file */
        $file = $this->fileRepository->findByUid($event->getFileUid());
        $this->setAlbumRelations($file);
    }

    public function afterFileUpdatedInIndexEvent(AfterFileUpdatedInIndexEvent $event): void
    {
        $this->setAlbumRelations($event->getFile());
    }

    protected function setAlbumRelations(File $file): void
    {
        $fileInfo = $file->getStorage()->getFileInfo($file);
        $this->albumRepository->updateAlbumsForFileUid(
            $file->getUid(),
            $fileInfo['folder_identifiers'] ?? []
        );
    }
}
