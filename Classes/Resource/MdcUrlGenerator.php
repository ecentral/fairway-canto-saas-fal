<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Resource;

use Ecentral\CantoSaasFal\Resource\Event\BeforeMdcUrlGenerationEvent;
use Ecentral\CantoSaasFal\Resource\Repository\CantoRepository;
use Ecentral\CantoSaasFal\Utility\CantoUtility;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\File;

final class MdcUrlGenerator
{
    // get image as a square, formatted as -B<image-size>
    public const BOXED = '-B';
    // get image scaled down to width+height, formatted as -S<width>x<height>
    public const SCALED = '-S';
    // get image formatted into a provided file extension, formatted as -F<FILE_EXT> [JPG, WEBP, PNG, TIF, GIF, JP2 (JPG2000)]
    public const FORMATTED = '-F';
    // get image cropped to an area, formatted as -C<width>x<height>,<x>,<y>
    public const CROPPED = '-C';

    private CantoRepository $cantoRepository;
    private EventDispatcher $eventDispatcher;

    public function __construct(CantoRepository $cantoRepository, EventDispatcher $eventDispatcher)
    {
        $this->cantoRepository = $cantoRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function generateMdcUrl(File $file, array $configuration): string
    {
        $assetId = CantoUtility::getIdFromCombinedIdentifier($file->getIdentifier());
        $transformedConfiguration = $this->transformConfiguration($file, $configuration);
        return $this->cantoRepository->generateMdcUrl($assetId) . $this->addOperationToMdcUrl($transformedConfiguration);
    }

    /**
     * @return array{width: int, height: int}
     */
    public function resolveImageWidthHeight(File $file, array $processingConfiguration): array
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
        // @todo are there alternatives than Area ?
        $crop = $configuration['crop'] instanceof Area;
        $scaleString = '';
        $formatString = '';
        $cropString = '';
        if ($configuration['size']) {
            $scaleString = self::BOXED . $configuration['size'];
        }
        if (!$scaleString && $configuration['width'] && $configuration['height']) {
            $scaleString = self::SCALED . (int)$configuration['width'] . 'x' . (int)$configuration['height'];
        }
        if ($configuration['format']) {
            $formatString = self::FORMATTED . $configuration['format'];
        }
        if ($crop) {
            $croppingArea = $configuration['crop'];
            assert($croppingArea instanceof Area);
            $cropString = self::CROPPED . (int)$croppingArea->getWidth() . 'x' . (int)$croppingArea->getHeight();
            $cropString .= ',' . (int)$croppingArea->getOffsetLeft() . ',' . (int)$croppingArea->getOffsetTop();
        }
        $event = new BeforeMdcUrlGenerationEvent($configuration, $scaleString, $cropString, $formatString, true);
        return $this->eventDispatcher->dispatch($event)->getMdcUrl();
    }

    /**
     * @param array{width: ?int, height: ?int, minWidth: null|numeric, minHeight: null|numeric, maxWidth: null|numeric, maxHeight: null|numeric, crop: ?Area} $configuration
     * @return array{width: int, height: int, size?: ?int, x?: ?int, y?: ?int, format?: ?string, crop: ?Area, minWidth?: null|numeric, minHeight?: null|numeric, maxWidth?: null|numeric, maxHeight?: null|numeric} $configuration
     */
    private function transformConfiguration(File $file, array $configuration): array
    {
        $scheme = CantoUtility::getSchemeFromCombinedIdentifier($file->getIdentifier());
        $identifier = CantoUtility::getIdFromCombinedIdentifier($file->getIdentifier());
        $fileData = $this->cantoRepository->getFileDetails($scheme, $identifier, true);

        if ($configuration['width'] && $configuration['height']) {
            $configuration['height'] = (int)$configuration['height'];
            $configuration['width'] = (int)$configuration['width'];
            return $configuration;
        }
        $configuration['height'] = $configuration['height'] ?? $configuration['maxHeight'] ?? $fileData['height'];
        $configuration['width'] = $configuration['width'] ?? $configuration['maxWidth'] ?? $fileData['width'];
        if ($configuration['crop'] instanceof Area) {
            $configuration['height'] = min($configuration['height'], $configuration['crop']->getHeight());
            $configuration['width'] = min($configuration['width'], $configuration['crop']->getWidth());
        }
        $configuration['height'] = (int)$configuration['height'];
        $configuration['width'] = (int)$configuration['width'];
        if ($configuration['width'] === $configuration['height']) {
            $configuration['size'] = $configuration['width'];
        }
        if (isset($configuration['fileExtension'])) {
            $configuration['format'] = strtoupper($configuration['fileExtension']);
        }
        return $configuration;
    }
}
