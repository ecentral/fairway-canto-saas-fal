<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasApiClient\Http\Asset;

use Psr\Http\Message\ResponseInterface;

class BatchUpdatePropertiesResponse implements \Ecentral\CantoSaasApiClient\Http\ResponseInterface
{
    protected string $body;

    public function __construct(ResponseInterface $response)
    {
        $response->getBody()->rewind();
        $this->body = $response->getBody()->getContents();
    }

    public function getBody(): string
    {
        return $this->body;
    }
}
