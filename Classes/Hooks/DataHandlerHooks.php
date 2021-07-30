<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Hooks;

use Ecentral\CantoSaasFal\Resource\Driver\CantoDriver;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;

class DataHandlerHooks
{
    protected FrontendInterface $cantoFolderCache;

    protected FrontendInterface $cantoFileCache;

    public function __construct(FrontendInterface $cantoFolderCache, FrontendInterface $cantoFileCache)
    {
        $this->cantoFolderCache = $cantoFolderCache;
        $this->cantoFileCache = $cantoFileCache;
    }

    public function processDatamap_afterAllOperations(DataHandler $dataHandler): void
    {
        $this->clearCantoCache($dataHandler);
    }

    protected function clearCantoCache(DataHandler $dataHandler): void
    {
        foreach ($dataHandler->datamap ?? [] as $table => $data) {
            if ($table === 'sys_file_storage') {
                foreach ($data as $uid => $values) {
                    if (($values['driver'] ?? '') === CantoDriver::DRIVER_NAME) {
                        $tag = sprintf('canto_storage_%s', $uid);
                        $this->cantoFileCache->flushByTag($tag);
                        $this->cantoFolderCache->flushByTag($tag);
                    }
                }
            }
        }
    }
}
