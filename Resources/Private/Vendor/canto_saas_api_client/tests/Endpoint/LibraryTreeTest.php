<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasApiClient\Tests\Endpoint;

use Ecentral\CantoSaasApiClient\Client;
use Ecentral\CantoSaasApiClient\ClientOptions;
use Ecentral\CantoSaasApiClient\Endpoint\LibraryTree;
use Ecentral\CantoSaasApiClient\Http\LibraryTree\GetTreeRequest;
use Ecentral\CantoSaasApiClient\Http\LibraryTree\ListContentRequest;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class LibraryTreeTest extends TestCase
{
    /**
     * @test
     */
    public function listFolderContentSuccessfulObtainResponse(): void
    {
        $responseBody = '{' .
            '"facets":[{"key":"facet-value"}],' .
            '"results":[{"result-id":1234}],' .
            '"limit":100,' .
            '"found":500,' .
            '"sortBy":"name",' .
            '"sortDirection":"ascending",' .
            '"matchExpr":"test"' .
            '}';
        $mockHandler = new MockHandler([new Response(200, [], $responseBody)]);
        $clientMock = $this->buildClientMock($mockHandler);

        $libraryTreeEndpoint = new LibraryTree($clientMock);
        $response = $libraryTreeEndpoint->listContent($this->buildListFolderContentRequestMock());

        self::assertSame([['key' => 'facet-value']], $response->getFacets());
        self::assertSame([['result-id' => 1234]], $response->getResults());
        self::assertSame(100, $response->getLimit());
        self::assertSame(500, $response->getFound());
        self::assertSame('name', $response->getSortBy());
        self::assertSame('ascending', $response->getSortDirection());
        self::assertSame('test', $response->getMatchExpr());
    }

    /**
     * @test
     */
    public function listFolderContentExpectNotAuthorizedException(): void
    {
        self::expectExceptionCode(1626717511);

        $mockHandler = new MockHandler([new Response(401, [], '[]')]);
        $clientMock = $this->buildClientMock($mockHandler);

        $libraryTreeEndpoint = new LibraryTree($clientMock);
        $libraryTreeEndpoint->listContent($this->buildListFolderContentRequestMock());
    }

    /**
     * @test
     */
    public function listFolderContentExpectUnexpectedHttpStatusException(): void
    {
        self::expectExceptionCode(1626717610);

        $mockHandler = new MockHandler([new Response(400, [], '[]')]);
        $clientMock = $this->buildClientMock($mockHandler);

        $libraryTreeEndpoint = new LibraryTree($clientMock);
        $libraryTreeEndpoint->listContent($this->buildListFolderContentRequestMock());
    }

    /**
     * @test
     */
    public function getTreeSuccessfulObtainResponse(): void
    {
        $responseBody = '{"results":[{"id":"test"}],"sortBy":"time","sortDirection":"descending"}';
        $mockHandler = new MockHandler([new Response(200, [], $responseBody)]);
        $clientMock = $this->buildClientMock($mockHandler);

        $libraryTreeEndpoint = new LibraryTree($clientMock);
        $response = $libraryTreeEndpoint->getTree($this->buildGetTreeRequestMock());

        self::assertSame([['id' => 'test']], $response->getResults());
        self::assertSame('time', $response->getSortBy());
        self::assertSame('descending', $response->getSortDirection());
    }

    /**
     * @test
     */
    public function getTreeExpectNotAuthorizedException(): void
    {
        self::expectExceptionCode(1626717511);

        $mockHandler = new MockHandler([new Response(401, [], '[]')]);
        $clientMock = $this->buildClientMock($mockHandler);

        $libraryTreeEndpoint = new LibraryTree($clientMock);
        $libraryTreeEndpoint->getTree($this->buildGetTreeRequestMock());
    }

    /**
     * @test
     */
    public function getTreeExpectUnexpectedHttpStatusException(): void
    {
        self::expectExceptionCode(1626717610);

        $mockHandler = new MockHandler([new Response(400, [], '[]')]);
        $clientMock = $this->buildClientMock($mockHandler);

        $libraryTreeEndpoint = new LibraryTree($clientMock);
        $libraryTreeEndpoint->getTree($this->buildGetTreeRequestMock());
    }

    protected function buildClientMock(MockHandler $mockHandler): MockObject
    {
        $optionsMock = $this->getMockBuilder(ClientOptions::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCantoName', 'getCantoDomain'])
            ->getMock();
        $optionsMock->method('getCantoName')->willReturn('test');
        $optionsMock->method('getCantoDomain')->willReturn('canto.com');

        $httpClient = new HttpClient([
            'handler' => HandlerStack::create($mockHandler),
        ]);

        $clientMock = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getHttpClient', 'getLogger', 'getOptions', 'getAccessToken'])
            ->getMock();
        $clientMock->method('getHttpClient')->willReturn($httpClient);
        $clientMock->method('getLogger')->willReturn(new NullLogger());
        $clientMock->method('getOptions')->willReturn($optionsMock);
        $clientMock->method('getAccessToken')->willReturn(null);

        return $clientMock;
    }

    protected function buildListFolderContentRequestMock(): MockObject
    {
        $requestMock = $this->getMockBuilder(ListContentRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQueryParams', 'getPathVariables'])
            ->getMock();
        $requestMock->method('getQueryParams')->willReturn(null);
        $requestMock->method('getPathVariables')->willReturn(null);
        return $requestMock;
    }

    protected function buildGetTreeRequestMock(): MockObject
    {
        $requestMock = $this->getMockBuilder(GetTreeRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQueryParams', 'getPathVariables'])
            ->getMock();
        $requestMock->method('getQueryParams')->willReturn(null);
        $requestMock->method('getPathVariables')->willReturn(null);
        return $requestMock;
    }
}
