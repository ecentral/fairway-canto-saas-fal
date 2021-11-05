<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Resource\Processing;

use Ecentral\CantoSaasApiClient\Endpoint\Authorization\AuthorizationFailedException;
use Ecentral\CantoSaasFal\Resource\Driver\CantoDriver;
use Ecentral\CantoSaasFal\Resource\Repository\CantoRepository;
use Ecentral\CantoSaasFal\Utility\CantoUtility;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Resource\Processing\ProcessorInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CantoPreviewProcessor implements ProcessorInterface
{
    protected CantoRepository $cantoRepository;

    public function __construct(CantoRepository $cantoRepository)
    {
        $this->cantoRepository = $cantoRepository;
    }

    public function canProcessTask(TaskInterface $task): bool
    {
        return $task->getName() === 'Preview'
            && $task->getSourceFile()->getStorage()->getDriverType() === CantoDriver::DRIVER_NAME
            && !CantoUtility::useMdcCDN($task->getSourceFile()->getIdentifier());
    }

    /**
     * @throws AuthorizationFailedException
     */
    public function processTask(TaskInterface $task): void
    {
        $this->cantoRepository->initialize(
            $task->getSourceFile()->getStorage()->getUid(),
            $task->getSourceFile()->getStorage()->getConfiguration()
        );
        $helper = $this->getHelper();
        $result = $helper->process($task);

        if (!empty($result['filePath']) && file_exists($result['filePath'])) {
            $task->setExecuted(true);
            $imageDimensions = $this->getGraphicalFunctionsObject()->getImageDimensions($result['filePath']);
            $task->getTargetFile()->setName($task->getTargetFileName());
            $task->getTargetFile()->updateProperties(
                ['width' => $imageDimensions[0], 'height' => $imageDimensions[1], 'size' => filesize($result['filePath']), 'checksum' => $task->getConfigurationChecksum()]
            );
            $task->getTargetFile()->updateWithLocalFile($result['filePath']);
        } else {
            $task->setExecuted(false);
        }
    }

    protected function getGraphicalFunctionsObject(): GraphicalFunctions
    {
        return GeneralUtility::makeInstance(GraphicalFunctions::class);
    }

    protected function getHelper(): CantoPreviewHelper
    {
        $helper = GeneralUtility::makeInstance(CantoPreviewHelper::class);
        $helper->setCantoRepository($this->cantoRepository);
        return $helper;
    }
}
