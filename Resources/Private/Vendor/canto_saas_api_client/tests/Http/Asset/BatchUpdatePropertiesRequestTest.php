<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasApiClient\Tests\Http\Asset;

use Ecentral\CantoSaasApiClient\Http\Asset\BatchUpdatePropertiesRequest;
use PHPUnit\Framework\TestCase;

class BatchUpdatePropertiesRequestTest extends TestCase
{
    /**
     * @test
     */
    public function createRequestWithDefaultConfig(): void
    {
        $request = new BatchUpdatePropertiesRequest();

        self::assertSame('{"contents":[],"properties":[]}', $request->getBody());
        self::assertNull($request->getQueryParams());
        self::assertNull($request->getPathVariables());
    }

    /**
     * @test
     */
    public function createRequestWithDefaultConfig123(): void
    {
        $request = new BatchUpdatePropertiesRequest();
        $request->addAsset('my-file', 'image');
        $request->addProperty('keyword', 'kword1,kword2');

        self::assertSame(
            '{"contents":[{"id":"my-file","scheme":"image"}],"properties":[{"propertyId":"keyword","propertyValue":"kword1,kword2","action":"","customField":false}]}',
            $request->getBody()
        );
    }
}
