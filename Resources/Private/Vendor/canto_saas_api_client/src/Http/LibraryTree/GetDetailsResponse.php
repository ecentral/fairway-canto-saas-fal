<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasApiClient\Http\LibraryTree;

use Ecentral\CantoSaasApiClient\Http\InvalidResponseException;
use Ecentral\CantoSaasApiClient\Http\Response;
use Psr\Http\Message\ResponseInterface;

class GetDetailsResponse extends Response
{
    protected array $responseData;

    /**
     * @throws InvalidResponseException
     */
    public function __construct(ResponseInterface $response)
    {
        $this->responseData = $this->parseResponse($response);
    }

    public function getResponseData(): array
    {
        return $this->responseData;
    }
}
