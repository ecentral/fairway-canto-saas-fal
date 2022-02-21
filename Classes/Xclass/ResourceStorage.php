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
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
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
        if ($folder !== null && $action === 'writeFolder') {
            return CantoUtility::getSchemeFromCombinedIdentifier($folder->getIdentifier()) !== 'folder';
        }
        return parent::checkFolderActionPermission($action, $folder);
    }
}
