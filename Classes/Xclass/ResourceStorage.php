<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Xclass;

use Ecentral\CantoSaasFal\Resource\Driver\CantoDriver;
use Ecentral\CantoSaasFal\Resource\Repository\CantoFileIndexRepository;
use Ecentral\CantoSaasFal\Utility\CantoUtility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Resource\Driver\StreamableDriverInterface;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\Exception\NotImplementedMethodException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ResourceStorage extends \TYPO3\CMS\Core\Resource\ResourceStorage
{
    protected function getFileIndexRepository(): FileIndexRepository
    {
        if ($this->getDriverType() === CantoDriver::DRIVER_NAME) {
            return GeneralUtility::makeInstance(CantoFileIndexRepository::class);
        }
        return parent::getFileIndexRepository();
    }

    public function checkFolderActionPermission($action, Folder $folder = null)
    {
        if ($folder !== null && $action === 'writeFolder' && $this->getDriverType() === CantoDriver::DRIVER_NAME) {
            return CantoUtility::getSchemeFromCombinedIdentifier($folder->getIdentifier()) !== 'folder';
        }
        return parent::checkFolderActionPermission($action, $folder);
    }

    /**
     * Returns a PSR-7 Response which can be used to stream the requested file
     *
     * @param FileInterface $file
     * @param bool $asDownload If set Content-Disposition attachment is sent, inline otherwise
     * @param string $alternativeFilename the filename for the download (if $asDownload is set)
     * @param string $overrideMimeType If set this will be used as Content-Type header instead of the automatically detected mime type.
     * @return ResponseInterface
     */
    public function streamFile(
        FileInterface $file,
        bool $asDownload = false,
        string $alternativeFilename = null,
        string $overrideMimeType = null
    ): ResponseInterface {
        if (!$this->driver instanceof StreamableDriverInterface) {
            return $this->getPseudoStream($file, $asDownload, $alternativeFilename, $overrideMimeType);
        }

        $properties = [
            'as_download' => $asDownload,
            'filename_overwrite' => $alternativeFilename,
            'mimetype_overwrite' => $overrideMimeType,
        ];
        if ($file instanceof ProcessedFile && $file->usesOriginalFile()) {
            $file = $file->getOriginalFile();
        }
        if (is_callable([$file->getStorage()->getDriver(), 'streamFile'])) {
            return $file->getStorage()->getDriver()->streamFile($file->getIdentifier(), $properties);
        }
        throw new NotImplementedMethodException('streamFile has not been implemented for Driver ' . get_class($file->getStorage()->getDriver()));
    }
}
