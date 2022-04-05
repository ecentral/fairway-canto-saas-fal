<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Resource\Processing;

use Ecentral\CantoSaasFal\Resource\Driver\CantoDriver;
use Ecentral\CantoSaasFal\Resource\MdcUrlGenerator;
use Ecentral\CantoSaasFal\Resource\Repository\CantoRepository;
use Ecentral\CantoSaasFal\Utility\CantoUtility;
use TYPO3\CMS\Core\Resource\Processing\ProcessorInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

final class CantoMdcProcessor implements ProcessorInterface
{
    private CantoRepository $cantoRepository;
    private MdcUrlGenerator $mdcUrlGenerator;

    public function __construct(CantoRepository $cantoRepository, MdcUrlGenerator $mdcUrlGenerator)
    {
        $this->cantoRepository = $cantoRepository;
        $this->mdcUrlGenerator = $mdcUrlGenerator;
    }

    /**
     * @param TaskInterface $task
     * @return bool
     */
    public function canProcessTask(TaskInterface $task)
    {
        if ($task->getSourceFile()->getStorage()->getDriverType() === CantoDriver::DRIVER_NAME) {
            $identifier = $task->getTargetFile()->getIdentifier();
            if (CantoUtility::isMdcActivated($task->getSourceFile()->getStorage()->getConfiguration())) {
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
        $url = $this->mdcUrlGenerator->generateMdcUrl($task->getSourceFile(), $task->getConfiguration());
        $task->getTargetFile()->setName($task->getTargetFileName());
        $task->getTargetFile()->updateProcessingUrl($url);
        $task->getTargetFile()->setIdentifier(CantoUtility::identifierToProcessedIdentifier($fullFileIdentifier));
        $properties = $task->getTargetFile()->getProperties();
        $properties = array_merge(
            $properties ?? [],
            $this->mdcUrlGenerator->resolveImageWidthHeight(
                $task->getSourceFile(),
                $task->getConfiguration()
            )
        );
        $properties['processing_url'] = $url;
        $task->getTargetFile()->updateProperties($properties ?? []);
        $task->setExecuted(true);
    }
}
