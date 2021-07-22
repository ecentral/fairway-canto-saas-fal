<?php

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

return [
    'import_canto_file' => [
        'path' => '/canto-saas-fal/import-canto-file',
        'access' => 'public',
        'target' => \Ecentral\CantoSaasFal\Controller\Backend\CantoAssetBrowserController::class . '::importFile',
    ],
    'search_canto_file' => [
        'path' => '/canto-saas-fal/search-canto-file',
        'access' => 'public',
        'target' => \Ecentral\CantoSaasFal\Controller\Backend\CantoAssetBrowserController::class . '::search',
    ],
];
