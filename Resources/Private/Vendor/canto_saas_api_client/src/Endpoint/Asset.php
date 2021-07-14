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
use Ecentral\CantoSaasApiClient\Http\InvalidRequestException;
use Ecentral\CantoSaasApiClient\Http\InvalidResponseException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;

class Asset extends AbstractEndpoint
{
    /**
     * @throws InvalidResponseException
     * @throws ClientExceptionInterface
     * @throws Authorization\NotAuthorizedException
     * @throws InvalidRequestException
     */
    public function batchUpdateProperties(BatchUpdatePropertiesRequest $request): BatchUpdatePropertiesResponse
    {
        $uri = $this->buildRequestUrl('batch/edit', $request);
        $httpRequest = new Request('PUT', $uri, [], $request->getBody());

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

        return new BatchUpdatePropertiesResponse($response);
    }
}
