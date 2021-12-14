<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Resource\Event;

use TYPO3\CMS\Core\Utility\PathUtility;

final class BeforeLocalFileProcessingEvent
{
    private array $fileData;
    private bool $preview;
    private ?string $sourcePath = null;
    private ?string $fileExtension = null;
    private string $scheme;

    public function __construct(array $fileData, string $scheme, bool $preview)
    {
        $this->fileData = $fileData;
        $this->preview = $preview;
        $this->scheme = $scheme;
    }

    public function getFileData(): array
    {
        return $this->fileData;
    }

    public function isForPreview(): bool
    {
        return $this->preview;
    }

    public function setSourcePath(string $sourcePath): void
    {
        $this->sourcePath = $sourcePath;
    }

    public function setFileExtension(string $fileExtension): void
    {
        $this->fileExtension = $fileExtension;
    }

    public function getSourcePath(): ?string
    {
        if ($this->sourcePath !== null) {
            return $this->sourcePath;
        }
        if ($this->isForPreview()) {
            return $this->fileData['url']['preview'] ?? null;
        }
        if ($this->scheme === 'image' && !in_array($this->getFileExtension(), $GLOBALS['CANTO_SAAS_FAL']['IMAGE_TYPES'], true)) {
            $this->setFileExtension('png');
            return $this->fileData['url']['PNG'] ?? null;
        }
        return $this->fileData['url']['directUrlOriginal'] ?? null;
    }

    public function getFileExtension(): string
    {
        if ($this->fileExtension !== null) {
            return $this->fileExtension;
        }
        if ($this->isForPreview()) {
            return 'jpg';
        }
        return PathUtility::pathinfo($this->fileData['name'], PATHINFO_EXTENSION);
    }
}
