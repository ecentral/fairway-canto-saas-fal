<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\CantoSaasFal\Resource\Processing;

use Fairway\CantoSaasFal\Resource\Repository\CantoRepository;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Processing\LocalPreviewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CantoPreviewHelper extends LocalPreviewHelper
{
    protected CantoRepository $cantoRepository;

    public function setCantoRepository(CantoRepository $cantoRepository): void
    {
        $this->cantoRepository = $cantoRepository;
    }

    protected function generatePreviewFromFile(File $file, array $configuration, string $targetFilePath): array
    {
        try {
            $tempFile = $this->cantoRepository->getFileForLocalProcessing($file->getIdentifier(), true);
        } catch (\Exception $e) {
            // Create a default image
            $graphicalFunctions = GeneralUtility::makeInstance(GraphicalFunctions::class);
            $graphicalFunctions->getTemporaryImageWithText(
                $targetFilePath,
                'Not imagefile!',
                'No ext!',
                $file->getName()
            );
            return [
                'filePath' => $targetFilePath,
            ];
        }

        $processedImagePath = $this->generatePreviewFromLocalFile($tempFile, $configuration, $targetFilePath);
        GeneralUtility::unlink_tempfile($tempFile);
        return $processedImagePath;
    }
}
