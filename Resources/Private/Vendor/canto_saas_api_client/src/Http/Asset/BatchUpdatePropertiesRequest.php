<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasApiClient\Http\Asset;

use Ecentral\CantoSaasApiClient\Http\InvalidRequestException;
use Ecentral\CantoSaasApiClient\Http\RequestInterface;

class BatchUpdatePropertiesRequest implements RequestInterface
{
    protected array $assets = [];

    protected array $properties = [];

    public function addAsset(string $id, string $scheme): BatchUpdatePropertiesRequest
    {
        $this->assets[] = [
            'id' => $id,
            'scheme' => $scheme,
        ];
        return $this;
    }

    public function addProperty(string $id, string $value, string $action = '', bool $customField = false): BatchUpdatePropertiesRequest
    {
        $this->properties[] = [
            'propertyId' => $id,
            'propertyValue' => $value,
            'action' => $action,
            'customField' => $customField,
        ];
        return $this;
    }

    public function getQueryParams(): ?array
    {
        return null;
    }

    public function getPathVariables(): ?array
    {
        return null;
    }

    /**
     * @throws InvalidRequestException
     */
    public function getBody(): string
    {
        $bodyData = [
            'contents' => $this->assets,
            'properties' => $this->properties,
        ];

        try {
            return \json_encode($bodyData, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidRequestException(
                'Can not generate json http body.',
                1626885024,
                $e
            );
        }
    }
}
