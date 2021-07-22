<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Browser;

use Ecentral\CantoSaasFal\Resource\Driver\CantoDriver;
use Ecentral\CantoSaasFal\Resource\NoCantoStorageException;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\Browser\AbstractElementBrowser;
use TYPO3\CMS\Recordlist\Browser\ElementBrowserInterface;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;

class CantoAssetBrowser extends AbstractElementBrowser implements ElementBrowserInterface, LinkParameterProviderInterface
{
    protected ResourceStorage $storage;

    /**
     * @throws NoCantoStorageException
     */
    protected function initialize(): void
    {
        parent::initialize();
        $this->initializeView();
        $this->storage = $this->findFirstCantoStorage();
        $this->pageRenderer->loadRequireJsModule(
            'TYPO3/CMS/CantoSaasFal/BrowseCantoAssets',
            'function(BrowseCantoAssets) { new BrowseCantoAssets(' . $this->storage->getUid() . '); }'
        );
        $this->pageRenderer->addCssFile(
            'EXT:canto_saas_fal/Resources/Public/Css/CantoAssetBrowser.css'
        );
    }

    protected function getBodyTagAttributes(): array
    {
        return [
            'data-mode' => 'canto',
            'storage-uid' => (string)$this->storage->getUid(),
        ];
    }

    public function render(): string
    {
        $this->setBodyTagParameters();
        $this->moduleTemplate->setTitle(
            $this->getLanguageService()->sL(
                'LLL:EXT:canto_saas_fal/Resources/Private/Language/locallang_be.xlf:canto_asset_browser.title'
            )
        );
        $this->moduleTemplate->getView()->setTemplate('Search');
        $this->moduleTemplate->getView()->assignMultiple([
            'storage' => $this->storage,
        ]);
        return $this->moduleTemplate->renderContent();
    }

    /**
     * Needs to stay to prevent error with phpstan because of wrong parent return declaration.
     * @param mixed[] $data
     * @return array
     */
    public function processSessionData($data): array
    {
        return [$data, false];
    }

    public function getScriptUrl(): string
    {
        return $this->thisScript;
    }

    public function getUrlParameters(array $values): array
    {
        return [
            'mode' => 'canto',
            'bparams' => $this->bparams
        ];
    }

    public function isCurrentlySelectedItem(array $values): bool
    {
        return false;
    }

    protected function initializeView(): void
    {
        $view = $this->moduleTemplate->getView();
        $view->setLayoutRootPaths([
            100 => 'EXT:canto_saas_fal/Resources/Private/Layouts/'
        ]);
        $view->setPartialRootPaths([
            100 => 'EXT:canto_saas_fal/Resources/Private/Partials/',
        ]);
        $view->setTemplateRootPaths([
            100 => 'EXT:canto_saas_fal/Resources/Private/Templates/CantoAssetBrowser/'
        ]);
    }

    /**
     * @throws NoCantoStorageException
     */
    protected function findFirstCantoStorage(): ResourceStorage
    {
        $storages = $this->getStorageRepository()->findByStorageType(CantoDriver::DRIVER_NAME);
        if ($storages === []) {
            throw new NoCantoStorageException('No configured canto storage found', 1628164687);
        }
        return $storages[0];
    }

    protected function getStorageRepository(): StorageRepository
    {
        return GeneralUtility::makeInstance(StorageRepository::class);
    }
}
