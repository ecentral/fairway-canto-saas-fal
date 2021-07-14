<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasApiClient\Endpoint;

use Ecentral\CantoSaasApiClient\Endpoint\Authorization\NotAuthorizedException;
use Ecentral\CantoSaasApiClient\Http\InvalidResponseException;
use Ecentral\CantoSaasApiClient\Http\LibraryTree\GetTreeRequest;
use Ecentral\CantoSaasApiClient\Http\LibraryTree\GetTreeResponse;
use Ecentral\CantoSaasApiClient\Http\LibraryTree\ListContentRequest;
use Ecentral\CantoSaasApiClient\Http\LibraryTree\ListContentResponse;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;

class LibraryTree extends AbstractEndpoint
{
    /**
     * @throws ClientExceptionInterface
     * @throws InvalidResponseException
     * @throws NotAuthorizedException
     */
    public function listContent(ListContentRequest $request): ListContentResponse
    {
        $uri = $this->buildRequestUrl('folder', $request);
        $httpRequest = new Request('GET', $uri);

        $response = $this->sendRequest($httpRequest);

        if ($response->getStatusCode() !== 200) {
            throw new InvalidResponseException(
                sprintf(
                    'Invalid http status code received. Expected 200, got %s.',
                    $response->getStatusCode()
                ),
                1626717610,
                null,
                $response
            );
        }

        return new ListContentResponse($response);
    }

    /**
     * @throws InvalidResponseException
     * @throws ClientExceptionInterface
     * @throws NotAuthorizedException
     */
    public function getTree(GetTreeRequest $request): GetTreeResponse
    {
        $uri = $this->buildRequestUrl('tree', $request);
        $httpRequest = new Request('GET', $uri);

        $response = $this->sendRequest($httpRequest);

        if ($response->getStatusCode() !== 200) {
            throw new InvalidResponseException(
                sprintf(
                    'Invalid http status code received. Expected 200, got %s.',
                    $response->getStatusCode()
                ),
                1626717610,
                null,
                $response
            );
        }

        return new GetTreeResponse($response);
    }
}
