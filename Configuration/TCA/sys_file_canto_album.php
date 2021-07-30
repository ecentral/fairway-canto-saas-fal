<?php

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

return [
    'ctrl' => [
        'crdate' => 'crdate',
        'hideTable' => true,
        'label' => 'file',
        'rootLevel' => 1,
        'title' => 'File to Canto album relation',
    ],
    'columns' => [
        'file' => [
            'label' => 'File',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'sys_file',
                'maxitems' => 1,
                'minitems' => 0,
                'size' => 1,
            ],
        ],
        'album' => [
            'label' => 'Album id',
            'config' => [
                'readOnly' => true,
                'type' => 'input',
                'size' => 10,
            ]
        ],
    ],
    'types' => [
        0 => [
            'showitem' => 'file, album'
        ],
    ],
    'palettes' => [],
];
