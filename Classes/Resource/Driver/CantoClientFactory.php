<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Resource\Driver;

use Ecentral\CantoSaasApiClient\Client;
use Ecentral\CantoSaasApiClient\ClientOptions;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;

class CantoClientFactory implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function createClientFromDriverConfiguration(array $configuration): Client
    {
        $clientOptions = new ClientOptions([
            'cantoName' => $configuration['cantoName'],
            'cantoDomain' => $configuration['cantoDomain'],
            'appId' => $configuration['appId'],
            'appSecret' => $configuration['appSecret'],
            'httpClient' => GuzzleClientFactory::getClient(),
            'logger' => $this->logger,
        ]);
        return new Client($clientOptions);
    }
}
