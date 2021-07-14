<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasApiClient\Http;

use Psr\Http\Message\ResponseInterface;

abstract class Response implements \Ecentral\CantoSaasApiClient\Http\ResponseInterface
{
    /**
     * @throws InvalidResponseException
     */
    protected function parseResponse(ResponseInterface $response): array
    {
        $response->getBody()->rewind();
        $content = $response->getBody()->getContents();

        if (strlen($content) === 0) {
            throw new InvalidResponseException(
                'Empty response received',
                1626434956
            );
        }

        try {
            $json = \json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidResponseException(
                'Invalid json response received',
                1626434988,
                $e
            );
        }

        return $json;
    }
}
