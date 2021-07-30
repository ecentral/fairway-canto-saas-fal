<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Utility;

class CantoUtility
{
    const SCHEME_FOLDER = 'folder';
    const SCHEME_ALBUM = 'album';

    public static function isFolder(string $scheme): bool
    {
        return $scheme === self::SCHEME_FOLDER || $scheme === self::SCHEME_ALBUM;
    }

    public static function buildCombinedIdentifier(string $scheme, string $id): string
    {
        return sprintf('%s#%s', $scheme, $id);
    }

    /**
     * @throw \InvalidArgumentException
     */
    public static function getSchemeFromCombinedIdentifier(string $combinedIdentifier): string
    {
        $identifierParts = explode('#', $combinedIdentifier);
        if (count($identifierParts) !== 2) {
            throw new \InvalidArgumentException(
                'Invalid combined identifier given.',
                1626954151
            );
        }
        return $identifierParts[0];
    }

    /**
     * @throw \InvalidArgumentException
     */
    public static function getIdFromCombinedIdentifier(string $combinedIdentifier): string
    {
        $identifierParts = explode('#', $combinedIdentifier);
        if (count($identifierParts) !== 2) {
            throw new \InvalidArgumentException(
                'Invalid combined identifier given.',
                1626954176
            );
        }
        return $identifierParts[1];
    }

    public static function buildTimestampFromCantoDate(string $cantoDate): int
    {
        $dateTime = \DateTime::createFromFormat('YmdHisv', $cantoDate);
        return $dateTime->getTimestamp();
    }
}
