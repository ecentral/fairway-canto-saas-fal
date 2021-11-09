<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Utility;

use DateTime;
use InvalidArgumentException;

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
     * return format: array{identifier: string, scheme: string, mdc: bool}
     * phpstan seems to have a bug here, not identifying properly that the keys actually exist, thus we for now just return array here
     * @return array associative array with scheme, identifier and mcd-support-flag
     *
     * @throw \InvalidArgumentException
     */
    public static function splitCombinedIdentifier(string $combinedIdentifier): array
    {
        if (!self::isValidCombinedIdentifier($combinedIdentifier)) {
            throw new InvalidArgumentException(
                'Invalid combined identifier given: ' . $combinedIdentifier,
                1626954151
            );
        }
        $identification = array_combine(['scheme', 'identifier'], explode('#', $combinedIdentifier));
        $identification['mdc'] = str_contains($identification['scheme'], self::SCHEME_CDN_TOKEN);
        $scheme = str_replace(self::SCHEME_CDN_TOKEN, '', $identification['scheme']);
        assert(is_string($scheme));
        $identification['scheme'] = $scheme;
        return $identification;
    }

    public static function buildCombinedIdentifier(string $scheme, string $id, bool $withCdnPrefix = false): string
    {
        // todo: after changing the behaviour of self::isMdcActivated we need to adjust this functionality
        //  afterwards every file, no matter whether its mdc or not will have the same identifier
        //  we still might provide the sys_file_reference with a "from-mdc" flag, to make file-based separation still possible
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
     * todo: this method will be fairly extended:
     *  - @see FAIRCANTO-63
     *  - provide EventDispatcher to enable or disable MDC globally
     *  - limit MDC-usage for certain file-extensions
     * @throw \InvalidArgumentException
     */
    public static function isMdcActivated(string $combinedIdentifier): bool
    {
        return self::splitCombinedIdentifier($combinedIdentifier)['mdc'];
    }

    public static function buildTimestampFromCantoDate(string $cantoDate): int
    {
        return DateTime::createFromFormat('YmdHisv', $cantoDate)->getTimestamp();
    }

    public static function identifierToProcessedIdentifier(string $identifier): string
    {
        if (str_contains($identifier, self::PROCESSING_PREFIX)) {
            return $identifier;
        }
        return self::PROCESSING_PREFIX . $identifier;
    }
}
