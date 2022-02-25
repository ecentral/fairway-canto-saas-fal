<?php

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

// Register new fal driver
$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers'][\Ecentral\CantoSaasFal\Resource\Driver\CantoDriver::DRIVER_NAME] = [
    'class' => \Ecentral\CantoSaasFal\Resource\Driver\CantoDriver::class,
    'shortName' => \Ecentral\CantoSaasFal\Resource\Driver\CantoDriver::DRIVER_NAME,
    'flexFormDS' => 'FILE:EXT:canto_saas_fal/Configuration/FlexForm/CantoDriver.xml',
    'label' => 'Canto DAM',
];

// Register canto specific file processors.
$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processors']['CantoPreviewProcessor'] = [
    'className' => \Ecentral\CantoSaasFal\Resource\Processing\CantoPreviewProcessor::class,
    'before' => [
        'SvgImageProcessor'
    ]
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processors']['CantoMdcProcessor'] = [
    'className' => \Ecentral\CantoSaasFal\Resource\Processing\CantoMdcProcessor::class,
    'before' => ['LocalImageProcessor'],
];

// Register XClasses to handle multi folder assignments.
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Resource\ResourceStorage::class] = [
    'className' => \Ecentral\CantoSaasFal\Xclass\ResourceStorage::class,
];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Resource\Index\Indexer::class] = [
    'className' => \Ecentral\CantoSaasFal\Xclass\Indexer::class,
];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Filelist\Controller\FileListController::class] = [
    'className' => \Ecentral\CantoSaasFal\Xclass\FileListController::class,
];

// Hooks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][1627626213]
    = \Ecentral\CantoSaasFal\Hooks\DataHandlerHooks::class;

// Override Inline node type to add canto asset button.
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1628070217] = [
    'nodeName' => 'inline',
    'priority' => 100,
    'class' => \Ecentral\CantoSaasFal\Form\Container\InlineControlContainer::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ElementBrowsers']['canto']
    = \Ecentral\CantoSaasFal\Browser\CantoAssetBrowser::class;

$extractorRegistry = \TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::getInstance();
$extractorRegistry->registerExtractionService(\Ecentral\CantoSaasFal\Resource\Metadata\Extractor::class);
unset($extractorRegistry);

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

$mediaFileExtensions = $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'];
$GLOBALS['CANTO_SAAS_FAL']['IMAGE_TYPES'] = explode(',', $mediaFileExtensions);

if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext']) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'] .= ',eps';
}

if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']) {
    $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] .= ',eps';
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Ecentral.CantoSaasFal',
    'metadataWebhook',
    [
        \Ecentral\CantoSaasFal\Controller\MetadataWebhookController::class => 'index',
    ],
    [
        \Ecentral\CantoSaasFal\Controller\MetadataWebhookController::class => 'index',
    ],
);

$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
$signalSlotDispatcher->connect(
    TYPO3\CMS\Backend\Controller\EditDocumentController::class,
    'initAfter',
    Ecentral\CantoSaasFal\Resource\EventListener\AfterFormEnginePageInitializedEventListener::class,
    'updateMetadataInCantoSlot'
);
