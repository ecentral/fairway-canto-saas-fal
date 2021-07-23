<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasApiClient\Tests\Http\Asset;

use Ecentral\CantoSaasApiClient\Http\Asset\BatchUpdatePropertiesResponse;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class BatchUpdatePropertiesResponseTest extends TestCase
{
    /**
     * @test
     */
    public function createValidResponse(): void
    {
        $httpResponse = new Response(200, [], 'success');
        $response = new BatchUpdatePropertiesResponse($httpResponse);

        self::assertSame('success', $response->getBody());
    }
}
