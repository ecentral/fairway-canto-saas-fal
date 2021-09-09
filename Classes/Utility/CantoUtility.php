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

    public static function isValidCombinedIdentifier(string $combinedIdentifier): bool
    {
        return count(explode('#', $combinedIdentifier)) === 2;
    }

    public static function isFolder(string $scheme): bool
    {
        return $scheme === self::SCHEME_FOLDER || $scheme === self::SCHEME_ALBUM;
    }

    /**
     * Split a combined identifier into its scheme and identifier
     *
     * @param string $combinedIdentifier
     * @return array associative array with scheme and identifier
     *
     * @throw \InvalidArgumentException
     */
    public static function splitCombinedIdentifier(string $combinedIdentifier): array
    {
        if (!self::isValidCombinedIdentifier($combinedIdentifier)) {
            throw new \InvalidArgumentException(
                'Invalid combined identifier given: ' . $combinedIdentifier,
                1626954151
            );
        }
        return array_combine(['scheme', 'identifier'], explode('#', $combinedIdentifier));
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
        return self::splitCombinedIdentifier($combinedIdentifier)['scheme'];
    }

    /**
     * @throw \InvalidArgumentException
     */
    public static function getIdFromCombinedIdentifier(string $combinedIdentifier): string
    {
        return self::splitCombinedIdentifier($combinedIdentifier)['identifier'];
    }

    public static function buildTimestampFromCantoDate(string $cantoDate): int
    {
        $dateTime = \DateTime::createFromFormat('YmdHisv', $cantoDate);
        return $dateTime->getTimestamp();
    }
}
