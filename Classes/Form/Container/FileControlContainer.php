<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\CantoSaasFal\Form\Container;

use TYPO3\CMS\Backend\Form\Container\FilesControlContainer as FilesControlContainerCore;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class FileControlContainer extends FilesControlContainerCore
{
    /**
     * Generate buttons to select, reference and upload files.
     */
    protected function getFileSelectors(array $inlineConfiguration, FileExtensionFilter $fileExtensionFilter): array
    {
        $rval = parent::getFileSelectors($inlineConfiguration, $fileExtensionFilter);

        /** @var  StorageRepository $service */
        $storageReporitory = GeneralUtility::makeInstance(StorageRepository::class);
        $storages = $storageReporitory->findAll();

        foreach ($storages as $storage) {
            if($storage->getDriverType() == 'Canto') {
                if ($storage->getUid() > 0) {
                    $newbuttonData = $this->renderAssetPickerButton($inlineConfiguration, $storage->getUid(), $storage->getName());
                    $rval[count($rval)] = $newbuttonData;
                }
            }
        }
        return $rval;
    }

    /**
     * @param array<string, mixed> $inlineConfiguration
     * @param int $storageId
     * @return string
     */
    private function renderAssetPickerButton(array $inlineConfiguration, int $storageId, string $storageName): string
    {
        $buttonStyle = '';
        if (isset($inlineConfiguration['inline']['inlineNewRelationButtonStyle'])) {
            $buttonStyle = ' style="' . $inlineConfiguration['inline']['inlineNewRelationButtonStyle'] . '"';
        }

        $foreign_table = $inlineConfiguration['foreign_table'];
        $allowed = '';
        if(isset($inlineConfiguration['allowed'])) {
            $allowed = $inlineConfiguration['allowed'];
        }
        $currentStructureDomObjectIdPrefix = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix(
            $this->data['inlineFirstPid']
        );
        $objectPrefix = $currentStructureDomObjectIdPrefix . '-' . $foreign_table;

        $title = 'Add file';
        $title .= ' [' . $storageName . ']';

        $browserParams = '|||' . $allowed . '|' . $objectPrefix . '|' . $storageId;
        $icon = '';
        return <<<HTML
<button type="button" class="btn btn-default t3js-element-browser" data-mode="cantosaas" data-params="$browserParams" $buttonStyle title="$title">
$icon $title
</button>
HTML;
    }
}
