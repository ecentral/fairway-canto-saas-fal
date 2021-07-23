<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasApiClient\Tests\Http\LibraryTree;

use Ecentral\CantoSaasApiClient\Http\LibraryTree\GetTreeRequest;
use PHPUnit\Framework\TestCase;

class GetTreeRequestTest extends TestCase
{
    /**
     * @test
     */
    public function createRequestWithDefaultConfig(): void
    {
        $request = new GetTreeRequest();
        $expected = [
            'sortBy' => 'time',
            'sortDirection' => 'ascending',
            'layer' => -1,
        ];

        self::assertSame($expected, $request->getQueryParams());
        self::assertNull($request->getPathVariables());
    }

    /**
     * @test
     */
    public function setSort(): void
    {
        $request = new GetTreeRequest();
        $request->setSortBy('name');

        self::assertSame('name', $request->getQueryParams()['sortBy']);
    }

    /**
     * @test
     */
    public function setSortDirection(): void
    {
        $request = new GetTreeRequest();
        $request->setSortDirection('descending');

        self::assertSame('descending', $request->getQueryParams()['sortDirection']);
    }

    /**
     * @test
     */
    public function setLayer(): void
    {
        $request = new GetTreeRequest();
        $request->setLayer(3);

        self::assertSame(3, $request->getQueryParams()['layer']);
    }
}
