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
use Ecentral\CantoSaasApiClient\Http\LibraryTree\GetDetailsRequest;
use Ecentral\CantoSaasApiClient\Http\LibraryTree\GetDetailsResponse;
use Ecentral\CantoSaasApiClient\Http\LibraryTree\GetTreeRequest;
use Ecentral\CantoSaasApiClient\Http\LibraryTree\GetTreeResponse;
use Ecentral\CantoSaasApiClient\Http\LibraryTree\ListAlbumContentRequest;
use Ecentral\CantoSaasApiClient\Http\LibraryTree\ListAlbumContentResponse;
use Ecentral\CantoSaasApiClient\Http\LibraryTree\SearchFolderRequest;
use Ecentral\CantoSaasApiClient\Http\LibraryTree\SearchFolderResponse;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;

class LibraryTree extends AbstractEndpoint
{
    /**
     * @throws InvalidResponseException
     * @throws NotAuthorizedException
     */
    public function searchFolderContent(SearchFolderRequest $request): SearchFolderResponse
    {
        $uri = $this->buildRequestUrl('folder', $request);
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

        return new SearchFolderResponse($response);
    }

    /**
     * @throws InvalidResponseException
     * @throws NotAuthorizedException
     */
    public function listAlbumContent(ListAlbumContentRequest $request): ListAlbumContentResponse
    {
        $uri = $this->buildRequestUrl('album', $request);
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

        return new ListAlbumContentResponse($response);
    }

    /**
     * @throws InvalidResponseException
     * @throws NotAuthorizedException
     */
    public function getTree(GetTreeRequest $request): GetTreeResponse
    {
        $uri = $this->buildRequestUrl('tree', $request);
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

        return new GetTreeResponse($response);
    }

    /**
     * @throws InvalidResponseException
     * @throws NotAuthorizedException
     */
    public function getDetails(GetDetailsRequest $request): GetDetailsResponse
    {
        if ($request->getType() === GetDetailsRequest::TYPE_FOLDER) {
            $uri = $this->buildRequestUrl('info/folder', $request);
        } else {
            $uri = $this->buildRequestUrl('info/album', $request);
        }
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

        return new GetDetailsResponse($response);
    }
}
