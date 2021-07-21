<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasApiClient\Endpoint\Authorization;

use Ecentral\CantoSaasApiClient\Endpoint\AbstractEndpoint;
use Ecentral\CantoSaasApiClient\Http\Authorization\OAuth2Request;
use Ecentral\CantoSaasApiClient\Http\Authorization\OAuth2Response;
use Ecentral\CantoSaasApiClient\Http\RequestInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Client\ClientExceptionInterface;

final class OAuth2 extends AbstractEndpoint
{
    /**
     * @throws AuthorizationFailedException
     */
    public function obtainAccessToken(OAuth2Request $request): OAuth2Response
    {
        $uri = $this->buildRequestUrl(
            'token',
            $request
        );
        $httpRequest = new Request('POST', $uri);

        try {
            $response = $this->sendRequest($httpRequest);
        } catch (ClientExceptionInterface $e) {
            throw new AuthorizationFailedException(
                $e->getMessage(),
                1626447895,
                $e
            );
        }

        if ($response->getStatusCode() !== 200) {
            $response->getBody()->rewind();
            throw new AuthorizationFailedException(
                sprintf(
                    'Could not authenticate with given config. Response status code: %s. Response text: %s',
                    $response->getStatusCode(),
                    $response->getBody()->getContents()
                ),
                1626437133
            );
        }

        return new OAuth2Response($response);
    }

    protected function buildRequestUrl(string $path, RequestInterface $request): Uri
    {
        $url = sprintf(
            'https://auth.%s/oauth/api/oauth2/%s',
            $this->getClient()->getOptions()->getCantoDomain(),
            urlencode(trim($path, '/'))
        );

        $queryParams = $request->getQueryParams();
        if (count($queryParams) > 0) {
            $url .= '?' . http_build_query($queryParams);
        }

        return new Uri($url);
    }
}
