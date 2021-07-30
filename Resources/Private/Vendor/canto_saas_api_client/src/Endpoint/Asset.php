<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasApiClient\Endpoint;

use Ecentral\CantoSaasApiClient\Http\Asset\BatchUpdatePropertiesRequest;
use Ecentral\CantoSaasApiClient\Http\Asset\BatchUpdatePropertiesResponse;
use Ecentral\CantoSaasApiClient\Http\Asset\GetContentDetailsRequest;
use Ecentral\CantoSaasApiClient\Http\Asset\GetContentDetailsResponse;
use Ecentral\CantoSaasApiClient\Http\Asset\SearchRequest;
use Ecentral\CantoSaasApiClient\Http\Asset\SearchResponse;
use Ecentral\CantoSaasApiClient\Http\InvalidRequestException;
use Ecentral\CantoSaasApiClient\Http\InvalidResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

class Asset extends AbstractEndpoint
{
    /**
     * @throws InvalidResponseException
     * @throws Authorization\NotAuthorizedException
     */
    public function search(SearchRequest $request): SearchResponse
    {
        $uri = $this->buildRequestUrl('search', $request);
        $httpRequest = new Request('GET', $uri);

        try {
            $response = $this->sendRequest($httpRequest);
        } catch (GuzzleException $e) {
            throw new InvalidResponseException(
                sprintf(
                    'Invalid http status code received. Expected 200, got %s.',
                    $e->getCode()
                ),
                1627649307,
                $e
            );
        }

        return new SearchResponse($response);
    }

    /**
     * @throws InvalidResponseException
     * @throws Authorization\NotAuthorizedException
     * @throws InvalidRequestException
     */
    public function batchUpdateProperties(BatchUpdatePropertiesRequest $request): BatchUpdatePropertiesResponse
    {
        $uri = $this->buildRequestUrl('batch/edit', $request);
        $httpRequest = new Request('PUT', $uri, [], $request->getBody());

        try {
            $response = $this->sendRequest($httpRequest);
        } catch (GuzzleException $e) {
            throw new InvalidResponseException(
                sprintf(
                    'Invalid http status code received. Expected 200, got %s.',
                    $e->getCode()
                ),
                1626717610,
                $e
            );
        }

        return new BatchUpdatePropertiesResponse($response);
    }

    /**
     * @throws InvalidResponseException
     * @throws Authorization\NotAuthorizedException
     */
    public function getContentDetails(GetContentDetailsRequest $request): GetContentDetailsResponse
    {
        $uri = $this->buildRequestUrl('', $request);
        $httpRequest = new Request('GET', $uri);

        try {
            $response = $this->sendRequest($httpRequest);
        } catch (GuzzleException $e) {
            throw new InvalidResponseException(
                sprintf(
                    'Invalid http status code received. Expected 200, got %s.',
                    $e->getCode()
                ),
                1626717610,
                $e
            );
        }

        return new GetContentDetailsResponse($response);
    }

    /**
     * @throws InvalidResponseException
     * @throws Authorization\NotAuthorizedException
     */
    public function getAuthorizedUrlContent(string $uri): ResponseInterface
    {
        $httpRequest = new Request('GET', $uri);
        try {
            return $this->sendRequest($httpRequest);
        } catch (GuzzleException $e) {
            throw new InvalidResponseException(
                sprintf(
                    'Invalid http status code received. Expected 200, got %s.',
                    $e->getCode()
                ),
                1627544102,
                $e
            );
        }
    }
}
