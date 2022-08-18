<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Utility;

use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class SiteConfigurationResolver
{
    /**
     * @param string $configurationKey
     * @return mixed|null
     */
    public static function get(string $configurationKey)
    {
        try {
            $site = (new self())->getCurrentSite();
            $siteConfiguration = $site->getConfiguration();
            return $siteConfiguration[$configurationKey] ?? null;
        } catch (SiteNotFoundException $exception) {
            return null;
        }
    }

    public function getCurrentSite(): Site
    {
        $request = $this->getTypo3Request();
        $site = $request ? $request->getAttribute('site') : null;
        // In (Ajax) Backend Requests, this is NullSite
        if ($site instanceof Site) {
            return $site;
        }
        $ajax = $request ? ($request->getParsedBody()['ajax'] ?? $request->getQueryParams()['ajax'] ?? null) : null;
        $returnUrl = '';
        if (!empty($ajax['context'])) {
            $config = [];
            try {
                $context = json_decode($ajax['context'], true, 512, JSON_THROW_ON_ERROR);
                $config = json_decode($context['config'], true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
            }
            if ($config) {
                $returnUrl = urldecode($config['originalReturnUrl'] ?? '');
            }
        }
        // should the site attribute not be set for a normal Request, them we fall back to the SiteFinder-Method way
        if ($request && !$returnUrl) {
            $returnUrl = $request->getQueryParams()['returnUrl'] ?? '';
        }
        $queryString = parse_url($returnUrl, PHP_URL_QUERY) ?? '';
        parse_str($queryString, $queryParams);
        $pageId = (int)($queryParams['id'] ?? 0);
        if ($returnUrl === '' || $pageId === 0) {
            $allSites = $this->getSiteFinder()->getAllSites();
            if (count($allSites) === 1) {
                // this is the absolute last fallback if all other methods should fail
                // that should guarantee that we always find on a single domain setup a SiteConfiguration
                // if the SiteConfiguration has been set up and no PseudoSite is returned
                $site = array_values($allSites)[0];
                if ($site instanceof Site) {
                    return $site;
                }
            }
            throw new SiteNotFoundException('Site configuration could not be determined.', 1628504403);
        }
        return $this->getSiteFinder()->getSiteByPageId($pageId);
    }

    private function getTypo3Request(): ?ServerRequest
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }

    private function getSiteFinder(): SiteFinder
    {
        return GeneralUtility::makeInstance(SiteFinder::class);
    }
}
