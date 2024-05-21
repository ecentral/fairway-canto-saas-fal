<?php

declare(strict_types=1);

/*
 * This file is part of the "pixelboxx_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\CantoSaasFal\Form\Container;

use TYPO3\CMS\Backend\Form\Container\FilesControlContainer as FilesControlContainerCore;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;

final class FileControlContainer extends FilesControlContainerCore
{
    /**
     * Generate buttons to select, reference and upload files.
     */
    protected function getFileSelectors(array $inlineConfiguration, FileExtensionFilter $fileExtensionFilter): array
    {
        $rval = parent::getFileSelectors($inlineConfiguration, $fileExtensionFilter);

        /** @var  DomainConfigurationService $service */
        //$service = GeneralUtility::makeInstance(DomainConfigurationServiceFactory::class)();
        $storageIds = [8];//$service->getAssetPickerStorageIds();

        foreach ($storageIds as $storageId) {

            if ($storageId > 0) {
                $newbuttonData = $this->renderPixelboxxAssetPickerButton($inlineConfiguration, $storageId, count($storageIds) > 1);
                $rval[count($rval)] = $newbuttonData;

            }
        }
        return $rval;
    }

    /**
     * @param array<string, mixed> $inlineConfiguration
     * @param int $storageId
     * @return string
     */
    private function renderPixelboxxAssetPickerButton(array $inlineConfiguration, int $storageId, bool $renderStorageId = false): string
    {
        $buttonStyle = '';
        if (isset($inlineConfiguration['inline']['inlineNewRelationButtonStyle'])) {
            $buttonStyle = ' style="' . $inlineConfiguration['inline']['inlineNewRelationButtonStyle'] . '"';
        }

        $foreign_table = $inlineConfiguration['foreign_table'];
        $allowed = $inlineConfiguration['allowed'];
        $currentStructureDomObjectIdPrefix = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix(
            $this->data['inlineFirstPid']
        );
        $objectPrefix = $currentStructureDomObjectIdPrefix . '-' . $foreign_table;

        $title = 'Add Pixelboxx file';
        if ($renderStorageId) { // multiple storage configurations present, result in rendering ids behind buttton
            $title .= ' [' . $storageId . ']';
        }
        $browserParams = '|||' . $allowed . '|' . $objectPrefix . '|' . $storageId;
        $icon = '';
        return <<<HTML
<button type="button" class="btn btn-default t3js-element-browser" data-mode="cantosaas" data-params="$browserParams" $buttonStyle title="$title">
$icon $title
</button>
HTML;
    }

}
