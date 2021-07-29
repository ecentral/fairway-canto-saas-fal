<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasApiClient\Tests\Http\LibraryTree;

use Ecentral\CantoSaasApiClient\Http\LibraryTree\SearchFolderRequest;
use PHPUnit\Framework\TestCase;

class SearchFolderRequestTest extends TestCase
{
    /**
     * @test
     */
    public function createRequestWithDefaultConfig(): void
    {
        $request = new SearchFolderRequest('test');
        $expected = [
            'keyword' => '',
            'scheme' => '',
            'tags' => '',
            'keywords' => '',
            'approval' => '',
            'owner' => '',
            'fileSize' => '',
            'created' => '',
            'createdTime' => '',
            'uploadedTime' => '',
            'lastModified' => '',
            'dimension' => '',
            'resolution' => '',
            'orientation' => '',
            'duration' => '',
            'pageNumber' => '',
            'sortBy' => 'time',
            'sortDirection' => 'descending',
            'limit' => 100,
            'start' => 0,
            'exactMatch' => 'false'
        ];

        self::assertSame($expected, $request->getQueryParams());
        self::assertSame(['test'], $request->getPathVariables());
    }

    /**
     * @test
     */
    public function createRequestWithMissingFolderId(): void
    {
        self::expectErrorMessageMatches('/^Too few arguments/');

        new SearchFolderRequest();
    }

    /**
     * @test
     */
    public function setKeyword(): void
    {
        $request = new SearchFolderRequest('test');
        $request->setKeyword('photo');

        self::assertSame('photo', $request->getQueryParams()['keyword']);
    }

    /**
     * @test
     */
    public function setScheme(): void
    {
        $request = new SearchFolderRequest('test');
        $request->setScheme('image|video');

        self::assertSame('image|video', $request->getQueryParams()['scheme']);
    }

    /**
     * @test
     */
    public function setTags(): void
    {
        $request = new SearchFolderRequest('test');
        $request->setTags('tag1|tag2+tag3');

        self::assertSame('tag1|tag2+tag3', $request->getQueryParams()['tags']);
    }

    /**
     * @test
     */
    public function setKeywords(): void
    {
        $request = new SearchFolderRequest('test');
        $request->setKeywords('k1|k2+k3');

        self::assertSame('k1|k2+k3', $request->getQueryParams()['keywords']);
    }

    /**
     * @test
     */
    public function setApproval(): void
    {
        $request = new SearchFolderRequest('test');
        $request->setApproval('approved|pending');

        self::assertSame('approved|pending', $request->getQueryParams()['approval']);
    }

    /**
     * @test
     */
    public function setOwner(): void
    {
        $request = new SearchFolderRequest('test');
        $request->setOwner('test@example.tld');

        self::assertSame('test@example.tld', $request->getQueryParams()['owner']);
    }

    /**
     * @test
     */
    public function setFileSize(): void
    {
        $request = new SearchFolderRequest('test');
        $request->setFileSize(10, 10000);

        self::assertSame('10..10000', $request->getQueryParams()['fileSize']);
    }

    /**
     * @test
     */
    public function setCreated(): void
    {
        $request = new SearchFolderRequest('test');
        $request->setCreated(1626672536, 1626772536);

        self::assertSame('1626672536..1626772536', $request->getQueryParams()['created']);
    }

    /**
     * @test
     */
    public function setCreatedTime(): void
    {
        $request = new SearchFolderRequest('test');
        $request->setCreatedTime(1626672536, 1626772536);

        self::assertSame('1626672536..1626772536', $request->getQueryParams()['createdTime']);
    }

    /**
     * @test
     */
    public function setUploadedTime(): void
    {
        $request = new SearchFolderRequest('test');
        $request->setUploadedTime(1626672536, 1626772536);

        self::assertSame('1626672536..1626772536', $request->getQueryParams()['uploadedTime']);
    }

    /**
     * @test
     */
    public function setLastModified(): void
    {
        $request = new SearchFolderRequest('test');
        $request->setLastModified(1626672536, 1626772536);

        self::assertSame('1626672536..1626772536', $request->getQueryParams()['lastModified']);
    }

    /**
     * @test
     */
    public function setDimension(): void
    {
        $request = new SearchFolderRequest('test');
        $request->setDimension(300, 2000);

        self::assertSame('300..2000', $request->getQueryParams()['dimension']);
    }

    /**
     * @test
     */
    public function setResolution(): void
    {
        $request = new SearchFolderRequest('test');
        $request->setResolution(72, 300);

        self::assertSame('72..300', $request->getQueryParams()['resolution']);
    }

    /**
     * @test
     */
    public function setOrientation(): void
    {
        $request = new SearchFolderRequest('test');
        $request->setOrientation('square');

        self::assertSame('square', $request->getQueryParams()['orientation']);
    }

    /**
     * @test
     */
    public function setDuration(): void
    {
        $request = new SearchFolderRequest('test');
        $request->setDuration(30, 600);

        self::assertSame('30..600', $request->getQueryParams()['duration']);
    }

    /**
     * @test
     */
    public function setPageNumber(): void
    {
        $request = new SearchFolderRequest('test');
        $request->setPageNumber(1, 5);

        self::assertSame('1..5', $request->getQueryParams()['pageNumber']);
    }

    /**
     * @test
     */
    public function setSortBy(): void
    {
        $request = new SearchFolderRequest('test');
        $request->setSortBy('name');

        self::assertSame('name', $request->getQueryParams()['sortBy']);
    }

    /**
     * @test
     */
    public function setSortDirection(): void
    {
        $request = new SearchFolderRequest('test');
        $request->setSortDirection('ascending');

        self::assertSame('ascending', $request->getQueryParams()['sortDirection']);
    }

    /**
     * @test
     */
    public function setLimit(): void
    {
        $request = new SearchFolderRequest('test');
        $request->setLimit(50);

        self::assertSame(50, $request->getQueryParams()['limit']);
    }

    /**
     * @test
     */
    public function setStart(): void
    {
        $request = new SearchFolderRequest('test');
        $request->setStart(5);

        self::assertSame(5, $request->getQueryParams()['start']);
    }

    /**
     * @test
     */
    public function setExactMatch(): void
    {
        $request = new SearchFolderRequest('test');
        $request->setExactMatch(true);

        self::assertSame('true', $request->getQueryParams()['exactMatch']);
    }
}
