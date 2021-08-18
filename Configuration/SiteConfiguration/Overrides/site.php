<?php

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

$GLOBALS['SiteConfiguration']['site']['columns']['canto_enabled_asset_picker'] = [
    'label' => 'Enable canto asset picker',
    'onChange' => 'reload',
    'config' => [
        'type' => 'check',
        'renderType' => 'checkboxToggle',
        'default' => 0,
        'items' => [
            [
                0 => '',
                1 => ''
            ]
        ]
    ],
];

$GLOBALS['SiteConfiguration']['site']['columns']['canto_asset_picker_storage'] = [
    'label' => 'Default storage to use',
    'displayCond' => 'FIELD:canto_enabled_asset_picker:=:1',
    'config' => [
        'type' => 'select',
        'renderType' => 'selectSingle',
        'items' => [
            ['Auto', 0]
        ],
        'foreign_table' => 'sys_file_storage',
        'foreign_table_where' => 'AND deleted = 0 AND driver = "'
            . \Ecentral\CantoSaasFal\Resource\Driver\CantoDriver::DRIVER_NAME . '"',
        'default' => 0,
    ],
];

$GLOBALS['SiteConfiguration']['site']['types']['0']['showitem']
    .= ',--div--;Canto,canto_enabled_asset_picker,canto_asset_picker_storage';
