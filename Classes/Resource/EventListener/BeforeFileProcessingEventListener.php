<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\CantoSaasFal\Resource\EventListener;

use Fairway\CantoSaasFal\Resource\Driver\CantoDriver;
use Fairway\CantoSaasFal\Resource\MdcUrlGenerator;
use Fairway\CantoSaasFal\Resource\Repository\CantoRepository;
use Fairway\CantoSaasFal\Utility\CantoUtility;
use TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;

final class BeforeFileProcessingEventListener
{
    private CantoRepository $cantoRepository;
    private ProcessedFileRepository $processedFileRepository;
    private MdcUrlGenerator $mdcUrlGenerator;

    public function __construct(CantoRepository $cantoRepository, ProcessedFileRepository $processedFileRepository, MdcUrlGenerator $mdcUrlGenerator)
    {
        $this->cantoRepository = $cantoRepository;
        $this->processedFileRepository = $processedFileRepository;
        $this->mdcUrlGenerator = $mdcUrlGenerator;
    }

    public function __invoke(BeforeFileProcessingEvent $event)
    {
        if (!($event->getDriver() instanceof CantoDriver)) {
            return;
        }
        $processedFile = $event->getProcessedFile();

        $identifier = $processedFile->getOriginalFile()->getIdentifier();
        if (!CantoUtility::isMdcActivated($event->getFile()->getStorage()->getConfiguration())) {
            return;
        }
        $configuration = $event->getConfiguration();
        if ($event->getTaskType() === ProcessedFile::CONTEXT_IMAGEPREVIEW) {
            $configuration['fileExtension'] = 'jpg';
        }

        $url = $this->mdcUrlGenerator->generateMdcUrl($processedFile->getOriginalFile(), $configuration, true);
        $properties = $processedFile->getProperties() ?? [];
        $properties = array_merge($properties, $this->mdcUrlGenerator->resolveImageWidthHeight(
            $processedFile->getOriginalFile(),
            $configuration
        ));
        $properties['processing_url'] = $url;
        $processedFile->updateProperties($properties);
        $processedFile->setIdentifier(CantoUtility::identifierToProcessedIdentifier($identifier));
        if (empty($processedFile->getName())) {
            $processedFile->setName($processedFile->getOriginalFile()->getName());
        }
        $event->setProcessedFile($processedFile);
        $this->processedFileRepository->add($processedFile);
    }
}
