<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasApiClient\Tests;

use Ecentral\CantoSaasApiClient\Client;
use Ecentral\CantoSaasApiClient\ClientOptions;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ClientTest extends TestCase
{
    /**
     * @test
     */
    public function createObjectWithDefaultOptions(): void
    {
        $options = new ClientOptions([
            'cantoName' => 'not-empty',
            'appId' => 'not-empty',
            'appSecret' => 'not-empty',
        ]);
        $client = new Client($options);

        self::assertInstanceOf(ClientInterface::class, $client->getHttpClient());
        self::assertInstanceOf(LoggerInterface::class, $client->getLogger());
    }

    /**
     * @test
     */
    public function createObjectWithCustomHttpClient(): void
    {
        $clientOptionsMock = $this->getMockBuilder(ClientOptions::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getHttpClient', 'getLogger'])
            ->getMock();
        $clientOptionsMock->method('getHttpClient')->willReturn(new \GuzzleHttp\Client());
        $clientOptionsMock->method('getLogger')->willReturn(null);
        $client = new Client($clientOptionsMock);

        self::assertInstanceOf(\GuzzleHttp\Client::class, $client->getHttpClient());
    }

    /**
     * @test
     */
    public function createObjectWithCustomLogger(): void
    {
        $clientOptionsMock = $this->getMockBuilder(ClientOptions::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getHttpClient', 'getHttpClientOptions', 'getLogger'])
            ->getMock();
        $clientOptionsMock->method('getHttpClient')->willReturn(null);
        $clientOptionsMock->method('getHttpClientOptions')->willReturn([
            'debug' => false,
            'timeout' => 10,
            'userAgent' => 'test',
        ]);
        $clientOptionsMock->method('getLogger')->willReturn(new NullLogger());
        $client = new Client($clientOptionsMock);

        self::assertInstanceOf(NullLogger::class, $client->getLogger());
    }
}
