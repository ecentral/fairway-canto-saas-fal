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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class InlineControlContainer extends \TYPO3\CMS\Backend\Form\Container\InlineControlContainer
{
    protected function renderPossibleRecordsSelectorTypeGroupDB(array $inlineConfiguration): string
    {
        $buttons = parent::renderPossibleRecordsSelectorTypeGroupDB($inlineConfiguration);
        if ($this->cantoAssetPickerEnabled($inlineConfiguration)) {
            $buttons = $this->appendButton(
                $buttons,
                $this->renderCantoAssetPickerButton($inlineConfiguration)
            );
        }
        return $buttons;
    }

    protected function cantoAssetPickerEnabled(array $inlineConfiguration): bool
    {
        $isFileBrowser = ($inlineConfiguration['overrideChildTca']['columns']['uid_local']['config']['appearance']['elementBrowserType'] ?? '') === 'file';
        $storages = $this->getStorageRepository()->findByStorageType(CantoDriver::DRIVER_NAME);
        return $isFileBrowser && count($storages) > 0;
    }

    protected function renderCantoAssetPickerButton(array $inlineConfiguration): string
    {
        $buttonStyle = '';
        if (isset($inlineConfiguration['inline']['inlineNewRelationButtonStyle'])) {
            $buttonStyle = ' style="' . $inlineConfiguration['inline']['inlineNewRelationButtonStyle'] . '"';
        }
        $groupFieldConfiguration = $inlineConfiguration['selectorOrUniqueConfiguration']['config'];
        $foreign_table = $inlineConfiguration['foreign_table'];
        $allowed = $groupFieldConfiguration['allowed'];
        $currentStructureDomObjectIdPrefix = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);
        $objectPrefix = $currentStructureDomObjectIdPrefix . '-' . $foreign_table;
        if (is_array($groupFieldConfiguration['appearance'])) {
            if (isset($groupFieldConfiguration['appearance']['elementBrowserAllowed'])) {
                $allowed = $groupFieldConfiguration['appearance']['elementBrowserAllowed'];
            }
        }
        $title = 'Add canto file';
        $browserParams = '|||' . $allowed . '|' . $objectPrefix;
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
}
