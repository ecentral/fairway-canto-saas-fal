<?php
declare(strict_types=1);

namespace Ecentral\CantoSaasFal\Resource\Processing;


use Ecentral\CantoSaasFal\Resource\Driver\CantoDriver;
use Ecentral\CantoSaasFal\Resource\Repository\CantoRepository;
use Ecentral\CantoSaasFal\Utility\CantoUtility;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Processing\ProcessorInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

final class CantoMdcProcessor implements ProcessorInterface
{
    protected CantoRepository $cantoRepository;

    public function __construct(CantoRepository $cantoRepository)
    {
        $this->cantoRepository = $cantoRepository;
    }

    /**
     * @param TaskInterface $task
     * @return bool
     */
    public function canProcessTask(TaskInterface $task)
    {
        if ($task->getSourceFile()->getStorage()->getDriverType() === CantoDriver::DRIVER_NAME) {
            $identifier = $task->getTargetFile()->getIdentifier();
            if (CantoUtility::useMdcCDN($identifier)) {
                return true;
            }
        }
        return false;
    }

    public function processTask(TaskInterface $task)
    {
        $this->cantoRepository->initialize(
            $task->getSourceFile()->getStorage()->getUid(),
            $task->getSourceFile()->getStorage()->getConfiguration()
        );
        $fullFileIdentifier = $task->getTargetFile()->getIdentifier();
        $assetId = CantoUtility::getIdFromCombinedIdentifier($fullFileIdentifier);
        $configuration = $this->transformConfiguration($task->getSourceFile(), $task->getConfiguration());
        $scale = $configuration['crop'] === null;
        $url = $this->cantoRepository->generateMdcUrl($assetId) . self::addOperationToMdcUrl($configuration, $scale);
        $task->getTargetFile()->updateProcessingUrl($url);
        $properties = $task->getTargetFile()->getProperties();
        [$width, $height, ] = getimagesize($url);
        $properties['width'] = $width;
        $properties['height'] = $height;
        $task->getTargetFile()->updateProperties($properties ?? []);
        $task->setExecuted(true);
    }

    /**
     * @param array{width: string|int, height: string|int, size: int, x: int, y: int, format: string, crop: mixed} $configuration
     * @param bool $scale
     * @return string
     */
    public static function addOperationToMdcUrl(array $configuration, bool $scale): string
    {
        $crop = $configuration['crop'] !== null;
        $scaleString = '';
        $formatString = '';
        $cropString = '';
        if ($scale && $configuration['size']) {
            $scaleString = '-S' . $configuration['size'];
        }
        if ($scale && $configuration['width'] && $configuration['height']) {
            $scaleString = '-S' . $configuration['width'] . 'x' . $configuration['height'];
        }
        if ($configuration['format']) {
            $formatString = '-F' . $configuration['format'];
        }
        if ($crop && $configuration['width'] && $configuration['height']) {
            $cropString = '-C' . (int)$configuration['width'] . 'x' . (int)$configuration['height'];
        }
        if ($cropString && $configuration['x'] && $configuration['y']) {
            $cropString .= ',' . (int)$configuration['x'] . ',' . (int)$configuration['y'];
        }
        return sprintf('%s%s%s', $scaleString, $cropString, $formatString);
    }

    private function transformConfiguration(File $file, array $configuration): array
    {
        $scheme = CantoUtility::getSchemeFromCombinedIdentifier($file->getIdentifier());
        $identifier = CantoUtility::getIdFromCombinedIdentifier($file->getIdentifier());
        $fileData = $this->cantoRepository->getFileDetails($scheme, $identifier, true);

        if ($configuration['crop'] instanceof Area) {
            $area = $configuration['crop'];
            $configuration['width'] = $area->getWidth();
            $configuration['height'] = $area->getHeight();
            $configuration['x'] = $area->getOffsetLeft();
            $configuration['y'] = $area->getOffsetTop();
            return $configuration;
        }
        if ($configuration['width'] && $configuration['height']) {
            return $configuration;
        }
        $configuration['height'] = $configuration['height'] ?? $configuration['maxHeight'] ?? $fileData['height'];
        $configuration['width'] = $configuration['width'] ?? $configuration['maxWidth'] ?? $fileData['width'];
        $configuration['height'] = (int)$configuration['height'];
        $configuration['width'] = (int)$configuration['width'];
        return $configuration;
    }
}
