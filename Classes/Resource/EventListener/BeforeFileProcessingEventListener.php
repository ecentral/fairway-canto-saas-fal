<?php
declare(strict_types=1);

namespace Ecentral\CantoSaasFal\Resource\EventListener;


use Ecentral\CantoSaasFal\Resource\Driver\CantoDriver;
use Ecentral\CantoSaasFal\Resource\Repository\CantoRepository;
use Ecentral\CantoSaasFal\Utility\CantoMdcUrlProcessor;
use Ecentral\CantoSaasFal\Utility\CantoUtility;
use TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;

final class BeforeFileProcessingEventListener
{
    private CantoRepository $cantoRepository;
    private ProcessedFileRepository $processedFileRepository;

    public function __construct(CantoRepository $cantoRepository, ProcessedFileRepository $processedFileRepository)
    {
        $this->cantoRepository = $cantoRepository;
        $this->processedFileRepository = $processedFileRepository;
    }

    public function __invoke(BeforeFileProcessingEvent $event)
    {
        if (!($event->getDriver() instanceof CantoDriver)) {
            return;
        }
        $processedFile = $event->getProcessedFile();
        $identifier = $processedFile->getOriginalFile()->getIdentifier();
        if (!CantoUtility::useMdcCDN($identifier)) {
            return;
        }
        $processor = new CantoMdcUrlProcessor($this->cantoRepository);
        $configuration = $event->getConfiguration();
        if ($event->getTaskType() === ProcessedFile::CONTEXT_IMAGEPREVIEW) {
            $configuration['fileExtension'] = 'jpg';
        }
        $url = $processor->getCantoMdcUrl($processedFile->getOriginalFile(), $configuration);
        $properties = $processedFile->getProperties() ?? [];
        $properties = array_merge($properties, $processor->getImageWidthHeight(
            $processedFile->getOriginalFile(),
            $configuration
        ));
        $properties['processing_url'] = $url;
        $processedFile->updateProperties($properties);
        $processedFile->setIdentifier(CantoUtility::identifierToProcessedIdentifier($identifier));
        $event->setProcessedFile($processedFile);
        $this->processedFileRepository->add($processedFile);
    }
}
