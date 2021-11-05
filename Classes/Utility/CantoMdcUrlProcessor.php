<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Utility;

use Ecentral\CantoSaasFal\Resource\Repository\CantoRepository;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\File;

final class CantoMdcUrlProcessor
{
    private CantoRepository $cantoRepository;

    public function __construct(CantoRepository $cantoRepository)
    {
        $this->cantoRepository = $cantoRepository;
    }

    public function getCantoMdcUrl(File $file, array $configuration): string
    {
        $assetId = CantoUtility::getIdFromCombinedIdentifier($file->getIdentifier());
        $transformedConfiguration = $this->transformConfiguration($file, $configuration);
        return $this->cantoRepository->generateMdcUrl($assetId) . $this->addOperationToMdcUrl($transformedConfiguration);
    }

    /**
     * @return array{width: int, height: int}
     */
    public function getImageWidthHeight(File $file, array $processingConfiguration): array
    {
        $configuration = $this->transformConfiguration($file, $processingConfiguration);
        $width = min($configuration['width'], $processingConfiguration['maxWidth'] ?? PHP_INT_MAX);
        $height = min($configuration['height'], $processingConfiguration['maxHeight'] ?? PHP_INT_MAX);
        return [
            'width' => (int)$width,
            'height' => (int)$height,
        ];
    }

    /**
     * @param array{width: int, height: int, size?: int, x?: int, y?: int, format?: string, crop?: ?Area} $configuration
     * @return string
     */
    public function addOperationToMdcUrl(array $configuration): string
    {
        $scale = $this->enableScaling($configuration);
        // @todo are there alternatives than Area ?
        $crop = $configuration['crop'] instanceof Area;
        $scaleString = '';
        $formatString = '';
        $cropString = '';
        if ($scale && $configuration['size']) {
            $scaleString = '-B' . $configuration['size'];
        }
        if (!$scaleString && $configuration['width'] && $configuration['height']) {
            $scaleString = '-S' . $configuration['width'] . 'x' . $configuration['height'];
        }
        if ($configuration['format']) {
            $formatString = '-F' . $configuration['format'];
        }
        if ($crop) {
            $croppingArea = $configuration['crop'];
            assert($croppingArea instanceof Area);
            $cropString = '-C' . (int)$croppingArea->getWidth() . 'x' . (int)$croppingArea->getHeight();
            $cropString .= ',' . (int)$croppingArea->getOffsetLeft() . ',' . (int)$croppingArea->getOffsetTop();
        }
        return sprintf('%s%s%s', $scaleString, $cropString, $formatString);
    }

    /**
     * @param array{width: null|numeric, height: null|numeric, minWidth: null|numeric, minHeight: null|numeric, maxWidth: null|numeric, maxHeight: null|numeric, crop: ?Area} $configuration
     * @return array{width: int, height: int, size: ?int, x: ?int, y: ?int, format: ?string, crop: ?Area} $configuration
     */
    private function transformConfiguration(File $file, array $configuration): array
    {
        $scheme = CantoUtility::getSchemeFromCombinedIdentifier($file->getIdentifier());
        $identifier = CantoUtility::getIdFromCombinedIdentifier($file->getIdentifier());
        $fileData = $this->cantoRepository->getFileDetails($scheme, $identifier, true);

        if ($configuration['width'] && $configuration['height']) {
            return $configuration;
        }
        $configuration['height'] = $configuration['height'] ?? $configuration['maxHeight'] ?? $fileData['height'];
        $configuration['width'] = $configuration['width'] ?? $configuration['maxWidth'] ?? $fileData['width'];
        $configuration['height'] = (int)$configuration['height'];
        $configuration['width'] = (int)$configuration['width'];
        if ($configuration['crop'] instanceof Area) {
            $configuration['height'] = min($configuration['height'], $configuration['crop']->getHeight());
            $configuration['width'] = min($configuration['width'], $configuration['crop']->getWidth());
        }
        if ($configuration['width'] === $configuration['height']) {
            $configuration['size'] = $configuration['width'];
        }
        if (isset($configuration['fileExtension'])) {
            $configuration['format'] = strtoupper($configuration['fileExtension']);
        }
        return $configuration;
    }

    /**
     * @param array{width: null|numeric, height: null|numeric, minWidth: null|numeric, minHeight: null|numeric, maxWidth: null|numeric, maxHeight: null|numeric, crop: ?Area} $configuration
     * @return bool
     */
    private function enableScaling(array $configuration): bool
    {
        $filtered = array_filter($configuration);
        if (count($filtered) > 1) {
            return true;
        }
        if (count($filtered) === 1 && array_keys($filtered)[0] !== 'crop') {
            return true;
        }
        return false;
    }
}
