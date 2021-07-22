<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasApiClient;

use Ecentral\CantoSaasApiClient\Endpoint\Asset;
use Ecentral\CantoSaasApiClient\Endpoint\Authorization\OAuth2;
use Ecentral\CantoSaasApiClient\Endpoint\LibraryTree;
use Ecentral\CantoSaasApiClient\Http\Authorization\OAuth2Request;
use Ecentral\CantoSaasApiClient\Http\Authorization\OAuth2Response;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Client
{
    protected ClientOptions $options;

    protected LoggerInterface $logger;

    protected ClientInterface $httpClient;

    protected ?string $accessToken = null;

    public function __construct(ClientOptions $options)
    {
        $this->options = $options;
        $this->httpClient = $this->options->getHttpClient() ?? $this->buildHttpClient();
        $this->logger = $this->options->getLogger() ?? new NullLogger();
    }

    public function getOptions(): ClientOptions
    {
        return $this->options;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getHttpClient(): ClientInterface
    {
        return $this->httpClient;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): void
    {
        if (strlen($accessToken) > 0) {
            $this->accessToken = $accessToken;
        }
    }

    /**
     * @throws Endpoint\Authorization\AuthorizationFailedException
     */
    public function authorizeWithClientCredentials(string $userId = ''): OAuth2Response
    {
        $request = new OAuth2Request();
        $request->setAppId($this->options->getAppId())
            ->setAppSecret($this->options->getAppSecret())
            ->setRedirectUri($this->options->getRedirectUri());
        if ($userId !== '') {
            $request->setUserId($userId);
        }

        $OAuth2 = new OAuth2($this);
        $response = $OAuth2->obtainAccessToken($request);
        $this->setAccessToken($response->getAccessToken());

        return $response;
    }

    public function asset(): Asset
    {
        return new Asset($this);
    }

    public function libraryTree(): LibraryTree
    {
        return new LibraryTree($this);
    }

    protected function buildHttpClient(): ClientInterface
    {
        return new \GuzzleHttp\Client([
            'allow_redirects' => true,
            'connect_timeout' => (int)$this->options->getHttpClientOptions()['timeout'],
            'debug' => (bool)$this->options->getHttpClientOptions()['debug'],
            'headers' => [
                'userAgent' => $this->options->getHttpClientOptions()['userAgent'],
            ],
        ]);
    }
}
