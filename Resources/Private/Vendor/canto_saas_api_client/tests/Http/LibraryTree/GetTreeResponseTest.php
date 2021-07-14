<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasApiClient\Tests\Http\LibraryTree;

use Ecentral\CantoSaasApiClient\Http\LibraryTree\GetTreeResponse;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class GetTreeResponseTest extends TestCase
{
    /**
     * @test
     */
    public function createValidResponse(): void
    {
        $response = new GetTreeResponse(
            new Response(
                200,
                [],
                '{"results":[{"id":"test"}],"sortBy":"time","sortDirection":"descending"}'
            )
        );

        self::assertSame([['id' => 'test']], $response->getResults());
        self::assertSame('time', $response->getSortBy());
        self::assertSame('descending', $response->getSortDirection());
    }

    /**
     * @test
     */
    public function throwExceptionWithEmptyBody(): void
    {
        self::expectExceptionCode(1626434956);

        new GetTreeResponse(new Response(200, [], ''));
    }

    /**
     * @test
     */
    public function throwExceptionWithInvalidJsonBody(): void
    {
        self::expectExceptionCode(1626434988);

        new GetTreeResponse(new Response(200, [], 'invalid-json'));
    }
}
