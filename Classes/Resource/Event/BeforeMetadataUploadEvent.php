<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\CantoSaasFal\Resource\Event;

final class BeforeMetadataUploadEvent
{
    private string $scheme;
    private string $identifier;
    private array $properties;

    public function __construct(string $scheme, string $identifier, array $properties)
    {
        $this->scheme = $scheme;
        $this->identifier = $identifier;
        $this->properties = $properties;
    }

    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }
}
