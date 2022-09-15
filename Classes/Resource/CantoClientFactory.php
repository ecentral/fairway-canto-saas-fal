<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\CantoSaasFal\Resource;

use Fairway\CantoSaasApi\Client;
use Fairway\CantoSaasApi\ClientOptions;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\SingletonInterface;

class CantoClientFactory implements LoggerAwareInterface, SingletonInterface
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
