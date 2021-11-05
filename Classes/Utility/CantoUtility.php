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
    public const SCHEME_FOLDER = 'folder';
    public const SCHEME_ALBUM = 'album';
    private const SCHEME_CDN_TOKEN = 'cdn::';
    private const PROCESSING_PREFIX = 'processed::';

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
     * @return array{identifier: string, scheme: string, mdc: bool} associative array with scheme, identifier and mcd-support-flag
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
        $identification = array_combine(['scheme', 'identifier'], explode('#', $combinedIdentifier));
        $identification['mdc'] = str_contains($identification['scheme'], self::SCHEME_CDN_TOKEN);
        $identification['scheme'] = str_replace(self::SCHEME_CDN_TOKEN, '', $identification['scheme']);
        return $identification;
    }

    public static function buildCombinedIdentifier(string $scheme, string $id, bool $withCdnPrefix = false): string
    {
        $prefix = $withCdnPrefix ? self::SCHEME_CDN_TOKEN : '';
        return sprintf('%s%s#%s', $prefix, $scheme, $id);
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

    /**
     * @throw \InvalidArgumentException
     */
    public static function useMdcCDN(string $combinedIdentifier): bool
    {
        return self::splitCombinedIdentifier($combinedIdentifier)['mdc'];
    }

    public static function buildTimestampFromCantoDate(string $cantoDate): int
    {
        $dateTime = \DateTime::createFromFormat('YmdHisv', $cantoDate);
        return $dateTime->getTimestamp();
    }

    public static function identifierToProcessedIdentifier(string $identifier): string
    {
        if (str_contains($identifier, self::PROCESSING_PREFIX)) {
            return $identifier;
        }
        return self::PROCESSING_PREFIX . $identifier;
    }
}
