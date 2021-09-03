<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Form\Container;

use Ecentral\CantoSaasFal\Resource\Driver\CantoDriver;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class InlineControlContainer extends \TYPO3\CMS\Backend\Form\Container\InlineControlContainer
{
    protected function renderPossibleRecordsSelectorTypeGroupDB(array $inlineConfiguration): string
    {
        $buttons = parent::renderPossibleRecordsSelectorTypeGroupDB($inlineConfiguration);
        if (($storageId = $this->getTargetStorageId()) > 0
            && $this->cantoAssetPickerEnabled($inlineConfiguration, $storageId)) {
            $buttons = $this->appendButton(
                $buttons,
                $this->renderCantoAssetPickerButton($inlineConfiguration, $storageId)
            );
        }
        return $buttons;
    }

    protected function getTargetStorageId(): int
    {
        try {
            $site = $this->getCurrentSite();
        } catch (SiteNotFoundException $e) {
            return 0;
        }
        $storageId = 0;
        $siteConfiguration = $site->getConfiguration();
        if (($siteConfiguration['canto_enabled_asset_picker'] ?? false) === true) {
            $storageId = (int)$siteConfiguration['canto_asset_picker_storage'];
            if ($storageId === 0) {
                $storages = $this->getStorageRepository()->findByStorageType(CantoDriver::DRIVER_NAME);
                if (isset($storages[0])) {
                    $storageId = $storages[0]->getUid();
                }
            }
        }
        return $storageId;
    }

    protected function cantoAssetPickerEnabled(array $inlineConfiguration, int $storageId): bool
    {
        $UserTsConfig = $this->getBackendUser()->getTSConfig();
        $isFileBrowser = ($inlineConfiguration['overrideChildTca']['columns']['uid_local']['config']['appearance']['elementBrowserType'] ?? '') === 'file';
        $isAssetPickerAllowed = $this->getBackendUser()->isAdmin()
            || ($UserTsConfig['permissions.']['file.']['default.']['cantoAssetPicker'] ?? '0') === '1'
            || ($UserTsConfig['permissions.']['file.']['storage.'][$storageId . '.']['cantoAssetPicker'] ?? '0') === '1';
        return $isFileBrowser && $isAssetPickerAllowed;
    }

    protected function renderCantoAssetPickerButton(array $inlineConfiguration, int $storageId): string
    {
        $buttonStyle = '';
        if (isset($inlineConfiguration['inline']['inlineNewRelationButtonStyle'])) {
            $buttonStyle = ' style="' . $inlineConfiguration['inline']['inlineNewRelationButtonStyle'] . '"';
        }
        $groupFieldConfiguration = $inlineConfiguration['selectorOrUniqueConfiguration']['config'];
        $foreign_table = $inlineConfiguration['foreign_table'];
        $allowed = $groupFieldConfiguration['allowed'];
        $currentStructureDomObjectIdPrefix = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix(
            $this->data['inlineFirstPid']
        );
        $objectPrefix = $currentStructureDomObjectIdPrefix . '-' . $foreign_table;
        if (is_array($groupFieldConfiguration['appearance'])) {
            if (isset($groupFieldConfiguration['appearance']['elementBrowserAllowed'])) {
                $allowed = $groupFieldConfiguration['appearance']['elementBrowserAllowed'];
            }
        }
        $title = 'Add canto file';
        $browserParams = '|||' . $allowed . '|' . $objectPrefix . '|' . $storageId;
        return '
            <button type="button" class="btn btn-default t3js-element-browser" data-mode="canto" data-params="' . $browserParams . '"
                ' . $buttonStyle . ' title="' . $title . '">
                ' . $this->iconFactory->getIcon('actions-online-media-add', Icon::SIZE_SMALL)->render() . '
                ' . $title . '
            </button>';
    }

    /**
     * Append the new $buttonHtml after the last button inside $origHtml.
     */
    protected function appendButton(string $origHtml, string $buttonHtml): string
    {
        $lastButtonClosingTagPosition = strrpos($origHtml, '</button>') + 9; // 9 is the length of </button>
        return substr_replace($origHtml, $buttonHtml, $lastButtonClosingTagPosition, 0);
    }

    protected function getUriBuilder(): UriBuilder
    {
        return GeneralUtility::makeInstance(UriBuilder::class);
    }

    protected function getStorageRepository(): StorageRepository
    {
        return GeneralUtility::makeInstance(StorageRepository::class);
    }

    /**
     * @throws SiteNotFoundException
     */
    protected function getCurrentSite(): Site
    {
        $returnUrl = $this->getTypo3Request()->getQueryParams()['returnUrl'] ?? '';
        $queryString = parse_url($returnUrl, PHP_URL_QUERY) ?? '';
        parse_str($queryString, $queryParams);
        $pageId = (int)$queryParams['id'] ?? 0;
        if ($returnUrl === '' || $pageId === 0) {
            throw new SiteNotFoundException('Site configuration could not be determined.', 1628504403);
        }
        return $this->getSiteFinder()->getSiteByPageId($pageId);
    }

    protected function getTypo3Request(): ServerRequest
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }

    protected function getSiteFinder(): SiteFinder
    {
        return GeneralUtility::makeInstance(SiteFinder::class);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
