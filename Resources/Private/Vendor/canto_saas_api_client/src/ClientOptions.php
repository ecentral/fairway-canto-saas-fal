<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasApiClient;

use JetBrains\PhpStorm\ArrayShape;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

class ClientOptions
{
    const OPTIONS_SHAPE = [
        'cantoName' => 'string',
        'cantoDomain' => 'string',
        'appId' => 'string',
        'appSecret' => 'string',
        'redirectUri' => 'string',
        'httpClient' => ClientInterface::class,
        'httpClientOptions' => [
            'debug' => 'bool',
            'timeout' => 'int',
            'userAgent' => 'string',
        ],
        'logger' => LoggerInterface::class
    ];

    private string $cantoName;

    private string $cantoDomain;

    private string $appId;

    private string $appSecret;

    private string $redirectUri;

    private ?ClientInterface $httpClient;

    private array $httpClientOptions;

    private ?LoggerInterface $logger;

    /**
     * @throws \InvalidArgumentException
     */
    #[ArrayShape(self::OPTIONS_SHAPE)]
    public function __construct(array $options = [])
    {
        $this->validateOptions($options);

        $this->cantoName = $options['cantoName'];
        $this->cantoDomain = $options['cantoDomain'] ?? 'canto.com';
        $this->appId = $options['appId'];
        $this->appSecret = $options['appSecret'];
        $this->redirectUri = $options['redirectUri'] ?? '';
        $this->httpClient = $options['httpClient'] ?? null;
        $this->httpClientOptions = array_replace(
            [
                'debug' => false,
                'timeout' => 10,
                'userAgent' => 'Canto PHP API client',
            ],
            $options['httpClientOptions'] ?? []
        );
        $this->logger = $options['logger'] ?? null;
    }

    public function getCantoName(): string
    {
        return $this->cantoName;
    }

    public function getCantoDomain(): string
    {
        return $this->cantoDomain;
    }

    public function getAppId(): string
    {
        return $this->appId;
    }

    public function getAppSecret(): string
    {
        return $this->appSecret;
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    public function getHttpClient(): ?ClientInterface
    {
        return $this->httpClient;
    }

    public function getHttpClientOptions(): array
    {
        return $this->httpClientOptions;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function validateOptions(array $options)
    {
        if (is_string($options['cantoName']) === false || strlen($options['cantoName']) === 0) {
            throw new \InvalidArgumentException(
                'Option cantoName is not given or not a string.',
                1626849038
            );
        }
        if (is_string($options['appId']) === false || strlen($options['appId']) === 0) {
            throw new \InvalidArgumentException(
                'Option appId is not given or not a string.',
                1626849132
            );
        }
        if (is_string($options['appSecret']) === false || strlen($options['appSecret']) === 0) {
            throw new \InvalidArgumentException(
                'Option appSecret is not given or not a string.',
                1626849145
            );
        }
    }
}
