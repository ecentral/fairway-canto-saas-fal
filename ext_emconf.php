<?php

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

$EM_CONF['canto_saas_fal'] = [
    'title' => 'Canto SaaS FAL',
    'description' => 'Adds Canto SaaS FAL driver.',
    'category' => 'misc',
    'version' => '1.0.6',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-11.5.99',
            'filemetadata' => '10.4.0-11.5.99',
        ],
    ],
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'author' => 'Tim Schreiner',
    'author_email' => 'tim.schreiner@km2.de',
    'author_company' => 'eCentral GmbH',
];
