<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasApiClient\Http\Asset;

use Ecentral\CantoSaasApiClient\Http\Response;
use Psr\Http\Message\ResponseInterface;

class GetContentDetailsResponse extends Response
{
    const APPROVAL_STATUS_PENDING = 'pending';
    const APPROVAL_STATUS_RESTRICTED = 'restricted';
    const APPROVAL_STATUS_APPROVED = 'approved';

    protected array $responseData;

    public function __construct(ResponseInterface $response)
    {
        $this->responseData = $this->parseResponse($response);
    }

    public function getResponseData(): array
    {
        return $this->responseData;
    }
}
