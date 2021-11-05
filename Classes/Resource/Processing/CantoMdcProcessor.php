<?php
declare(strict_types=1);

namespace Ecentral\CantoSaasFal\Resource\Processing;


use Ecentral\CantoSaasFal\Resource\Driver\CantoDriver;
use Ecentral\CantoSaasFal\Resource\Repository\CantoRepository;
use Ecentral\CantoSaasFal\Utility\CantoMdcUrlProcessor;
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
        $processor = new CantoMdcUrlProcessor($this->cantoRepository);
        $url = $processor->getCantoMdcUrl($task->getSourceFile(), $task->getConfiguration());
        $task->getTargetFile()->updateProcessingUrl($url);
        $properties = $task->getTargetFile()->getProperties();
        $properties = array_merge($properties, $processor->getImageWidthHeight(
            $task->getSourceFile(),
            $task->getConfiguration(),
        ));
        $properties['processing_url'] = $url;
        $task->getTargetFile()->setIdentifier(CantoUtility::identifierToProcessedIdentifier($fullFileIdentifier));
        $task->getTargetFile()->updateProperties($properties ?? []);
        $task->setExecuted(true);
    }
}
