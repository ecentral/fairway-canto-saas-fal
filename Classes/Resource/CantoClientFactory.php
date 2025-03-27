<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\CantoSaasFal\Resource;

use Fairway\CantoSaasApi\Client;
use Fairway\CantoSaasApi\ClientOptions;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CantoClientFactory implements LoggerAwareInterface, SingletonInterface
{
    use LoggerAwareTrait;

    public function createClientFromDriverConfiguration(array $configuration): Client
    {
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        if ($typo3Version->getMajorVersion()>11) {
            $gizzleClient = GeneralUtility::makeInstance(GuzzleClientFactory::class)->getClient();
        } else {
            $gizzleClient = GuzzleClientFactory::getClient();
        }

        $clientOptions = new ClientOptions([
            'cantoName' => $configuration['cantoName'],
            'cantoDomain' => $configuration['cantoDomain'],
            'appId' => $configuration['appId'],
            'appSecret' => $configuration['appSecret'],
            'httpClient' => $gizzleClient,
            'logger' => $this->logger,
        ]);
        return new Client($clientOptions);
    }

    public function getCantoHierarchy($apiUrl, $accessToken): array
    {
        $gizzleClient = GeneralUtility::makeInstance(GuzzleClientFactory::class)->getClient();

        try {
            $response = $gizzleClient->request('GET', $apiUrl, [
                'query' => [
                    'sortBy' => 'name',
                    'sortDirection' => 'ascending'
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ]
            ]);

            $responseBody = $response->getBody()->getContents();
            $data = json_decode($responseBody, true);

            $hierarchy = [];
            if (isset($data['results'])) {
                foreach ($data['results'] as $item) {
                    $hierarchy[] = $this->processFolder($item);
                }
            }

            return $hierarchy;

        } catch (\Exception $e) {
            return [];
        }
    }

    private function processFolder($folder): array
    {
        $folderData = [
            'id' => $folder['id'],
            'name' => $folder['name'],
            'scheme' => $folder['scheme'],
            'size' => $folder['size'] ?? '0',
            'children' => []
        ];

        if (isset($folder['children']) && is_array($folder['children'])) {
            foreach ($folder['children'] as $child) {
                $folderData['children'][] = $this->processFolder($child);
            }
        }

        return $folderData;
    }

    public function getCantoAlbumContents($albumId): array
    {
        try {
            $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
            $accessToken = $extensionConfiguration->get('canto_saas_fal', 'access_token');
            $getApiUrl = $extensionConfiguration->get('canto_saas_fal', 'api_url');
            $apiUrl = $getApiUrl . 'album/' . $albumId;

            $gizzleClient = GeneralUtility::makeInstance(GuzzleClientFactory::class)->getClient();

            $response = $gizzleClient->request('GET', $apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ]
            ]);

            $responseBody = $response->getBody()->getContents();
            $data = json_decode($responseBody, true);

            $assets = [];

            if (isset($data['results']) && is_array($data['results'])) {
                foreach ($data['results'] as $item) {
                    $isImage = false;

                    if (isset($item['scheme']) && $item['scheme'] === 'image') {
                        $isImage = true;
                    } elseif (isset($item['default']['Content Type']) &&
                        strpos($item['default']['Content Type'], 'image/') === 0) {
                        $isImage = true;
                    } elseif (isset($item['name']) && preg_match('/\.(jpe?g|png|gif|bmp|svg|webp|tiff?)$/i', $item['name'])) {
                        $isImage = true;
                    }

                    if (!$isImage) {
                        continue;
                    }

                    $asset = [
                        'id' => $item['id'] ?? '',
                        'name' => $item['name'] ?? '',
                        'scheme' => $item['scheme'] ?? '',
                        'type' => isset($item['default']['Content Type']) ?
                            str_replace('image/', '', $item['default']['Content Type']) : 'unknown'
                    ];

                    if (isset($item['url']) && is_array($item['url'])) {
                        if (isset($item['url']['directUrlPreview'])) {
                            $asset['previewUrl'] = $item['url']['directUrlPreview'];
                        } elseif (isset($item['url']['preview'])) {
                            $asset['previewUrl'] = $item['url']['preview'];
                        } else {
                            foreach ($item['url'] as $urlKey => $urlValue) {
                                if (is_string($urlValue) && !empty($urlValue)) {
                                    $asset['previewUrl'] = $urlValue;
                                    break;
                                }
                            }
                        }
                    }

                    $assets[] = $asset;
                }
            }

            return $assets;

        } catch (\Exception $e) {
            return [];
        }
    }
}
