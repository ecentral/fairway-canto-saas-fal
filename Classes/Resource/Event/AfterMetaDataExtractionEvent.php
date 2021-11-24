<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Resource\Event;

final class AfterMetaDataExtractionEvent
{
    private array $metadata;
    private array $mapping;
    private array $fileData;

    public function __construct(array $metadata, array $mapping, array $fileData)
    {
        $this->metadata = $metadata;
        $this->mapping = $mapping;
        $this->fileData = $fileData;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getMapping(): array
    {
        return $this->mapping;
    }

    public function getFileData(): array
    {
        return $this->fileData;
    }
}
