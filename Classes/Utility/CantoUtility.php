<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\CantoSaasFal\Utility;

use DateTime;
use Fairway\CantoSaasFal\Resource\Event\MdcEnabledCheckEvent;
use Fairway\CantoSaasFal\Resource\Repository\CantoRepository;
use InvalidArgumentException;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CantoUtility
{
    public const SCHEME_FOLDER = 'folder';
    public const SCHEME_ALBUM = 'album';
    public const SPLIT_CHARACTER = '<>';
    private const PROCESSING_PREFIX = 'processed::';

    public static function isValidCombinedIdentifier(string $combinedIdentifier): bool
    {
        return count(explode(self::SPLIT_CHARACTER, $combinedIdentifier)) === 2;
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
     * @return array{scheme: string, identifier: string} associative array with scheme, identifier and mcd-support-flag
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
        return array_combine(['scheme', 'identifier'], explode(self::SPLIT_CHARACTER, $combinedIdentifier));
    }

    public static function buildCombinedIdentifier(string $scheme, string $id): string
    {
        return sprintf('%s%s%s', $scheme, self::SPLIT_CHARACTER, $id);
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

    public static function isMdcActivated(array $configuration): bool
    {
        if (empty($configuration['mdcDomainName']) || empty($configuration['mdcAwsAccountId'])) {
            return false;
        }
        $mdc = SiteConfigurationResolver::get('canto_mdc_enabled') ?? false;
        if (isset($GLOBALS['CANTO_SAAS_FAL']['OVERRIDE_MDC_IS_ENABLED'])) {
            $mdc = $GLOBALS['CANTO_SAAS_FAL']['OVERRIDE_MDC_IS_ENABLED'] ?? false;
        }
        $event = new MdcEnabledCheckEvent($mdc);
        /** @var MdcEnabledCheckEvent $dispatchedEvent */
        $dispatchedEvent = GeneralUtility::makeInstance(EventDispatcher::class)->dispatch($event);
        return $dispatchedEvent->isMdcEnabled();
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

    public static function flushCache(CantoRepository $repository): void
    {
        $cache = GeneralUtility::getContainer()->get('cache.canto_folder');
        $cache->flushByTags([$repository->getCantoCacheTag()]);
        $cache = GeneralUtility::getContainer()->get('cache.canto_file');
        $cache->flushByTags([$repository->getCantoCacheTag()]);
    }
}
