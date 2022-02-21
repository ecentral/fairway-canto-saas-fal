<?php

declare(strict_types=1);

namespace Ecentral\CantoSaasFal\Resource\Event;

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
