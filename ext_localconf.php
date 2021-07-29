<?php

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

// Register new fal driver
$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']['Canto'] = [
    'class' => \Ecentral\CantoSaasFal\Resource\Driver\CantoDriver::class,
    'shortName' => 'Canto',
    'flexFormDS' => 'FILE:EXT:canto_saas_fal/Configuration/FlexForm/CantoDriver.xml',
    'label' => 'Canto DAM',
];

// Register files and folder information cache
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['canto_saas_fal_folder'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['canto_saas_fal_folder'] = [
        'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
        'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
        'groups' => [
            'system',
            'canto',
        ],
        'options' => [
            'defaultLifetime' => 3600,
        ],
    ];
}
// TODO Check if this cache can be replaced using the sys_file table.
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['canto_saas_fal_file'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['canto_saas_fal_file'] = [
        'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
        'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
        'groups' => [
            'system',
            'canto',
        ],
        'options' => [
            'defaultLifetime' => 3600,
        ],
    ];
}
