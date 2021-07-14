<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasApiClient\Tests\Http\Authorization;

use Ecentral\CantoSaasApiClient\Http\Authorization\OAuth2Request;
use PHPUnit\Framework\TestCase;

class OAuth2RequestTest extends TestCase
{
    /**
     * @test
     */
    public function createRequestWithDefaultConfig(): void
    {
        $request = new OAuth2Request();
        $expected = [
            'app_id' => '',
            'app_secret' => '',
            'grant_type' => 'client_credentials',
            'redirect_uri' => '',
            'code' => '',
            'refresh_token' => '',
            'scope' => 'admin',
            'user_id' => '',
        ];

        self::assertEquals($expected, $request->getQueryParams());
        self::assertNull($request->getPathVariables());
    }

    /**
     * @test
     */
    public function setAppId(): void
    {
        $request = new OAuth2Request();
        $request->setAppId('app-id-1234');

        self::assertSame('app-id-1234', $request->getQueryParams()['app_id']);
    }

    /**
     * @test
     */
    public function setAppSecret(): void
    {
        $request = new OAuth2Request();
        $request->setAppSecret('app-secret-1234');

        self::assertSame('app-secret-1234', $request->getQueryParams()['app_secret']);
    }

    /**
     * @test
     */
    public function setGrantType(): void
    {
        $request = new OAuth2Request();
        $request->setGrantType('my_grant_type');

        self::assertSame('my_grant_type', $request->getQueryParams()['grant_type']);
    }

    /**
     * @test
     */
    public function setRedirectUri(): void
    {
        $request = new OAuth2Request();
        $request->setRedirectUri('http://localhost');

        self::assertSame('http://localhost', $request->getQueryParams()['redirect_uri']);
    }

    /**
     * @test
     */
    public function setCode(): void
    {
        $request = new OAuth2Request();
        $request->setCode('code-1234');

        self::assertSame('code-1234', $request->getQueryParams()['code']);
    }

    /**
     * @test
     */
    public function setRefreshToken(): void
    {
        $request = new OAuth2Request();
        $request->setRefreshToken('refresh-token-1234');

        self::assertSame('refresh-token-1234', $request->getQueryParams()['refresh_token']);
    }

    /**
     * @test
     */
    public function setScope(): void
    {
        $request = new OAuth2Request();
        $request->setScope('user');

        self::assertSame('user', $request->getQueryParams()['scope']);
    }

    /**
     * @test
     */
    public function setUserId(): void
    {
        $request = new OAuth2Request();
        $request->setUserId('test@example.tld');

        self::assertSame('test@example.tld', $request->getQueryParams()['user_id']);
    }
}
