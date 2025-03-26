<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\CantoSaasFal\Resource;

use Fairway\CantoSaasFal\Resource\Event\BeforeMdcUrlGenerationEvent;
use Fairway\CantoSaasFal\Resource\Repository\CantoRepository;
use Fairway\CantoSaasFal\Utility\CantoUtility;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\File;

/**
 * todo: add documentation
 */
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

    public function generateMdcUrl(File $file, array $configuration, bool $srcForImage = false): string
    {
        $assetId = CantoUtility::getIdFromCombinedIdentifier($file->getIdentifier());
        $transformedConfiguration = $this->transformConfiguration($file, $configuration);
        $mdcDocumentType = $this->getDocumentType($file);
        return $this->cantoRepository->generateMdcUrl($assetId, $mdcDocumentType, $srcForImage) . $this->addOperationToMdcUrl($transformedConfiguration);
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
     * @return array{width: int, height: int, scale: float}
     */
    private function getMasterImageDimensions(File $file): array
    {
        $scheme = CantoUtility::getSchemeFromCombinedIdentifier($file->getIdentifier());
        $identifier = CantoUtility::getIdFromCombinedIdentifier($file->getIdentifier());
        $fileData = $this->cantoRepository->getFileDetails($scheme, $identifier);

        $width = $fileData['width'] ?? $file->getProperty('width');
        $height = $fileData['height'] ?? $file->getProperty('height');

        $masterImageSize = $file->getStorage()->getConfiguration()['masterImageSize'];
        $scale = min(1, $masterImageSize / $width, $masterImageSize / $height);

        return [
            'width' => $scale * $width,
            'height' => $scale * $height,
            'scale' => $scale,
        ];
    }

    /**
     * @param array{width: int, height: int, size?: int, x?: int, y?: int, format?: string, crop?: ?Area, resizedCropped?: array{width: int, height: int, offsetLeft: int, offsetTop: int}} $configuration
     * @return string
     */
    public function addOperationToMdcUrl(array $configuration): string
    {
        // @todo are there alternatives than Area ?
        $crop = ($configuration['crop'] ?? null) instanceof Area;
        $scaleString = '';
        $formatString = '';
        $cropString = '';
        if (isset($configuration['size']) && $configuration['size'] > 0) {
            $scaleString = self::BOXED . $configuration['size'];
        }
        if (!$scaleString && isset($configuration['width']) && isset($configuration['height']) && $configuration['width'] > 0) {
            $scaleString = self::SCALED . (int)$configuration['width'];
            if ($configuration['height'] > 0) {
                $scaleString.= 'x' . (int)$configuration['height'];
            }
        }
        if ($scaleString == '' && isset($configuration['maxHeight']) && $configuration['maxHeight'] > 0 && !isset($configuration['size'])) {
            $scaleString = self::BOXED . $configuration['maxHeight'];
        }
        if (isset($configuration['format'])) {
            $formatString = self::FORMATTED . $configuration['format'];
        }
        if ($crop) {
            $croppingArea = $configuration['resizedCropped'];
            $cropString = self::CROPPED . $croppingArea['width'] . 'x' . $croppingArea['height'];
            $cropString .= ',' . (int)$croppingArea['offsetLeft'] . ',' . (int)$croppingArea['offsetTop'];
        }
        $event = new BeforeMdcUrlGenerationEvent($configuration, $scaleString, $cropString, $formatString, true);
        return $this->eventDispatcher->dispatch($event)->getMdcUrl();
    }

    /**
     * @param array{width: ?int, height: ?int, minWidth: numeric|null, minHeight: numeric|null, maxWidth: numeric|null, maxHeight: numeric|null, crop: ?Area} $configuration
     * @return array{width: int, height: int, size?: ?int, x?: ?int, y?: ?int, format?: ?string, crop: ?Area, minWidth?: numeric|null, minHeight?: numeric|null, maxWidth?: numeric|null, maxHeight?: numeric|null}
     */
    private function transformConfiguration(File $file, array $configuration): array
    {
        $imageDimension = $this->getMasterImageDimensions($file);
        if (!empty($configuration['width']) && !empty($configuration['height'])) {
            $configuration['height'] = (int)$configuration['height'];
            $configuration['width'] = (int)$configuration['width'];
            return $configuration;
        }
        if ($configuration['maxWidth'] >  $imageDimension['width']) {
            $configuration['width'] = $configuration['width'] ?? $configuration['maxWidth'] ?? $imageDimension['width'] ?? 0;
        } else {
            $configuration['width'] = $configuration['maxWidth'];
        }
        if ($configuration['maxHeight'] >  $imageDimension['height']) {
            $configuration['height'] = $configuration['height'] ?? $configuration['maxHeight'] ?? $imageDimension['height'] ?? 0;
        } else {
            $configuration['height'] = $configuration['maxHeight'];
        }

        if (($configuration['crop'] ?? null) instanceof Area) {
            $configuration['height'] = min($configuration['height'], $configuration['crop']->getHeight());
            $configuration['width'] = min($configuration['width'], $configuration['crop']->getWidth());
            $configuration['resizedCropped'] = [
                'width' => (int)($configuration['crop']->getWidth() * $imageDimension['scale']),
                'height' => (int)($configuration['crop']->getHeight() * $imageDimension['scale']),
                'offsetLeft' => (int)($configuration['crop']->getOffsetLeft() * $imageDimension['scale']),
                'offsetTop' => (int)($configuration['crop']->getOffsetTop() * $imageDimension['scale']),
            ];
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

    public function getDocumentType(File|string $file): string
    {
        [$mdcFileType,$mdcIdentifiere] = explode('<>', is_string($file)?$file:$file->getIdentifier());
        return $mdcFileType;
    }
}
