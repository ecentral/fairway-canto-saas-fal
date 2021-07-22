<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasApiClient\Http\Authorization;

use Ecentral\CantoSaasApiClient\Endpoint\Authorization\AuthorizationFailedException;
use Ecentral\CantoSaasApiClient\Http\InvalidResponseException;
use Ecentral\CantoSaasApiClient\Http\Response;
use Psr\Http\Message\ResponseInterface;

class OAuth2Response extends Response
{
    private string $accessToken = '';

    private int $expiresIn = 0;

    private string $tokenType = '';

    private ?string $refreshToken = null;

    /**
     * @throws AuthorizationFailedException
     */
    public function __construct(ResponseInterface $response)
    {
        try {
            $json = $this->parseResponse($response);
        } catch (InvalidResponseException $e) {
            throw new AuthorizationFailedException(
                'Authorization failed - ' . $e->getMessage(),
                1626449779,
                $e
            );
        }

        $this->accessToken = $json['accessToken'];
        $this->expiresIn = (int)$json['expiresIn'];
        $this->tokenType = $json['tokenType'];
        $this->refreshToken = $json['refreshToken'];
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }
}
