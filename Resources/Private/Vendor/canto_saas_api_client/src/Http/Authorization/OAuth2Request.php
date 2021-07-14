<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasApiClient\Http\Authorization;

use Ecentral\CantoSaasApiClient\Http\RequestInterface;

class OAuth2Request implements RequestInterface
{
    protected string $appId = '';

    protected string $appSecret = '';

    protected string $grantType = 'client_credentials';

    protected string $redirectUri = '';

    protected string $code = '';

    protected string $refreshToken = '';

    protected string $scope = 'admin';

    protected string $userId = '';

    public function setAppId(string $appId): OAuth2Request
    {
        $this->appId = $appId;
        return $this;
    }

    public function setAppSecret(string $appSecret): OAuth2Request
    {
        $this->appSecret = $appSecret;
        return $this;
    }

    public function setGrantType(string $grantType): OAuth2Request
    {
        $this->grantType = $grantType;
        return $this;
    }

    public function setRedirectUri(string $redirectUri): OAuth2Request
    {
        $this->redirectUri = $redirectUri;
        return $this;
    }

    public function setCode(string $code): OAuth2Request
    {
        $this->code = $code;
        return $this;
    }

    public function setRefreshToken(string $refreshToken): OAuth2Request
    {
        $this->refreshToken = $refreshToken;
        return $this;
    }

    public function setScope(string $scope): OAuth2Request
    {
        $this->scope = $scope;
        return $this;
    }

    public function setUserId(string $userId): OAuth2Request
    {
        $this->userId = $userId;
        return $this;
    }

    public function getQueryParams(): ?array
    {
        return [
            'app_id' => $this->appId,
            'app_secret' => $this->appSecret,
            'grant_type' => $this->grantType,
            'redirect_uri' => $this->redirectUri,
            'code' => $this->code,
            'refresh_token' => $this->refreshToken,
            'scope' => $this->scope,
            'user_id' => $this->userId,
        ];
    }

    public function getPathVariables(): ?array
    {
        return null;
    }
}
