<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasApiClient\Tests;

use Ecentral\CantoSaasApiClient\ClientOptions;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ClientOptionsTest extends TestCase
{
    /**
     * @test
     */
    public function useDefaultValues(): void
    {
        $clientOptions = new ClientOptions([
            'cantoName' => 'not-empty',
            'appId' => 'not-empty',
            'appSecret' => 'not-empty',
        ]);

        self::assertSame('canto.com', $clientOptions->getCantoDomain());
        self::assertSame('', $clientOptions->getRedirectUri());
        self::assertNull($clientOptions->getHttpClient());
        self::assertNull($clientOptions->getLogger());
        self::assertFalse($clientOptions->getHttpClientOptions()['debug']);
        self::assertSame(10, $clientOptions->getHttpClientOptions()['timeout']);
        self::assertSame('Canto PHP API client', $clientOptions->getHttpClientOptions()['userAgent']);
    }

    /**
     * @test
     */
    public function setCantoName(): void
    {
        $options = new ClientOptions([
            'appId' => 'no-empty',
            'appSecret' => 'no-empty',
            'cantoName' => 'My name',
        ]);
        self::assertSame('My name', $options->getCantoName());
    }

    /**
     * @test
     */
    public function setCantoDomain(): void
    {
        $options = new ClientOptions([
            'cantoName' => 'not-empty',
            'appId' => 'not-empty',
            'appSecret' => 'not-empty',
            'cantoDomain' => 'canto.global',
        ]);
        self::assertSame('canto.global', $options->getCantoDomain());
    }

    /**
     * @test
     */
    public function setAppId(): void
    {
        $options = new ClientOptions([
            'cantoName' => 'not-empty',
            'appSecret' => 'not-empty',
            'appId' => '12345-Abc',
        ]);
        self::assertSame('12345-Abc', $options->getAppId());
    }

    /**
     * @test
     */
    public function setAppSecret(): void
    {
        $options = new ClientOptions([
            'cantoName' => 'not-empty',
            'appId' => 'not-empty',
            'appSecret' => '12345-Abc',
        ]);
        self::assertSame('12345-Abc', $options->getAppSecret());
    }

    /**
     * @test
     */
    public function setRedirectUri(): void
    {
        $options = new ClientOptions([
            'cantoName' => 'not-empty',
            'appId' => 'not-empty',
            'appSecret' => 'not-empty',
            'redirectUri' => 'http:/localhost',
        ]);
        self::assertSame('http:/localhost', $options->getRedirectUri());
    }

    /**
     * @test
     */
    public function setHttpClient(): void
    {
        $options = new ClientOptions([
            'cantoName' => 'not-empty',
            'appId' => 'not-empty',
            'appSecret' => 'not-empty',
            'httpClient' => new Client(),
        ]);
        self::assertInstanceOf(Client::class, $options->getHttpClient());
    }

    /**
     * @test
     */
    public function setLogger(): void
    {
        $options = new ClientOptions([
            'cantoName' => 'not-empty',
            'appId' => 'not-empty',
            'appSecret' => 'not-empty',
            'logger' => new NullLogger(),
        ]);
        self::assertInstanceOf(NullLogger::class, $options->getLogger());
    }

    /**
     * @test
     */
    public function setHttpOptions(): void
    {
        $options = new ClientOptions([
            'cantoName' => 'not-empty',
            'appId' => 'not-empty',
            'appSecret' => 'not-empty',
            'httpClientOptions' => [
                'debug' => true,
                'timeout' => 30,
                'userAgent' => 'My api client',
            ],
        ]);
        self::assertTrue($options->getHttpClientOptions()['debug']);
        self::assertSame(30, $options->getHttpClientOptions()['timeout']);
        self::assertSame('My api client', $options->getHttpClientOptions()['userAgent']);
    }
}
