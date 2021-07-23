<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasApiClient\Endpoint;

use Ecentral\CantoSaasApiClient\Client;
use Ecentral\CantoSaasApiClient\Endpoint\Authorization\NotAuthorizedException;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractEndpoint
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    protected function getClient(): Client
    {
        return $this->client;
    }

    protected function buildRequestUrl(string $path, \Ecentral\CantoSaasApiClient\Http\RequestInterface $request): Uri
    {
        $url = sprintf(
            'https://%s.%s/api/v1/%s',
            $this->client->getOptions()->getCantoName(),
            $this->client->getOptions()->getCantoDomain(),
            urlencode(trim($path, '/'))
        );

        $pathVariables = $request->getPathVariables();
        $queryParams = $request->getQueryParams();
        if (is_array($pathVariables) === true) {
            $url .= '/' . urlencode(implode($pathVariables));
        }
        if (is_array($queryParams) && count($queryParams) > 0) {
            $url .= '?' . http_build_query($queryParams);
        }

        return new Uri($url);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NotAuthorizedException
     */
    protected function sendRequest(RequestInterface $request): ResponseInterface
    {
        $accessToken = $this->client->getAccessToken();
        if ($accessToken !== null) {
            $request = $request->withHeader(
                'Authorization',
                'Bearer ' . $accessToken
            );
        }

        $response = $this->client->getHttpClient()->sendRequest($request);
        if ($response->getStatusCode() === 401) {
            throw new NotAuthorizedException(
                'Not authorized',
                1626717511,
                null,
                $response
            );
        }

        return $response;
    }
}
