<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Resource\Metadata;

use Ecentral\CantoSaasApiClient\Endpoint\Authorization\AuthorizationFailedException;
use Ecentral\CantoSaasFal\Resource\Driver\CantoDriver;
use Ecentral\CantoSaasFal\Resource\Repository\CantoRepository;
use Ecentral\CantoSaasFal\Utility\CantoUtility;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\ExtractorInterface;

class Extractor implements ExtractorInterface
{
    protected CantoRepository $cantoRepository;

    public function __construct(CantoRepository $cantoRepository)
    {
        $this->cantoRepository = $cantoRepository;
    }

    public function getFileTypeRestrictions(): array
    {
        return [];
    }

    public function getDriverRestrictions(): array
    {
        return [];
    }

    public function getPriority(): int
    {
        return 10;
    }

    public function getExecutionPriority(): int
    {
        return 10;
    }

    public function canProcess(File $file): bool
    {
        return $file->getStorage()->getDriverType() === CantoDriver::DRIVER_NAME;
    }

    /**
     * @throws AuthorizationFailedException
     */
    public function extractMetaData(File $file, array $previousExtractedData = []): array
    {
        $this->cantoRepository->initialize(
            $file->getStorage()->getUid(),
            $file->getStorage()->getConfiguration()
        );
        $fileData = $this->fetchDataForFile($file);
        if ($fileData === null) {
            return $previousExtractedData;
        }
        return array_replace(
            $previousExtractedData,
            [
                'width' => (int)$fileData['width'] ?? 0,
                'height' => (int)$fileData['height'] ?? 0,
                'pages' => (int)$fileData['default']['Pages'],
                'creator' => $fileData['default']['Author'],
                'creator_tool' => $fileData['default']['Creation Tool'],
                'copyright' => $fileData['default']['Copyright'],
            ]
        );
    }

    protected function fetchDataForFile(File $file): ?array
    {
        $scheme = CantoUtility::getSchemeFromCombinedIdentifier($file->getIdentifier());
        $identifier = CantoUtility::getIdFromCombinedIdentifier($file->getIdentifier());
        return $this->cantoRepository->getFileDetails($scheme, $identifier);
    }
}
