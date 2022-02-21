<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Command;

use Ecentral\CantoSaasFal\Resource\Driver\CantoDriver;
use Ecentral\CantoSaasFal\Resource\Repository\CantoRepository;
use Fairway\CantoSaasApi\Endpoint\Authorization\AuthorizationFailedException;
use Fairway\CantoSaasApi\Endpoint\Authorization\NotAuthorizedException;
use Fairway\CantoSaasApi\Http\InvalidResponseException;
use Fairway\CantoSaasApi\Http\LibraryTree\GetTreeRequest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class WarmupCantoCache extends Command
{
    protected StorageRepository $storageRepository;

    protected FrontendInterface $cantoFolderCache;

    public function injectStorageRepository(StorageRepository $storageRepository): void
    {
        $this->storageRepository = $storageRepository;
    }

    public function injectCantoFolderCache(FrontendInterface $cantoFolderCache): void
    {
        $this->cantoFolderCache = $cantoFolderCache;
    }

    protected function configure(): void
    {
        $this->setDescription('Warmup canto caches for each storage that uses canto driver.')
            ->addArgument(
                'storageUid',
                InputArgument::OPTIONAL,
                'The storage uid to warmup cache for.',
                '0'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storages = $this->getStorages($input);
        /** @var ResourceStorage $storage */
        foreach ($storages as $storage) {
            try {
                $repository = $this->initializeRepository($storage);
                $this->warmupFolderCache($repository);
            } catch (AuthorizationFailedException | NotAuthorizedException | InvalidResponseException $e) {
                $output->writeln($e->getMessage());
                return 1;
            }
        }
        return 0;
    }

    protected function getStorages(InputInterface $input): array
    {
        $storages = [];
        $storageUid = (int)$input->getArgument('storageUid');
        if ($storageUid > 0) {
            $storage = $this->storageRepository->findByUid($storageUid);
            if ($storage && $storage->getDriverType() === CantoDriver::DRIVER_NAME) {
                $storages[] = $storage;
            }
        }
        if ($storages === []) {
            $storages = $this->storageRepository->findByStorageType(CantoDriver::DRIVER_NAME);
        }
        return $storages;
    }

    /**
     * @throws AuthorizationFailedException
     */
    protected function initializeRepository(ResourceStorage $storage): CantoRepository
    {
        $cantoRepository = GeneralUtility::makeInstance(CantoRepository::class);
        $cantoRepository->initialize(
            $storage->getUid(),
            $storage->getConfiguration()
        );
        return $cantoRepository;
    }

    protected function warmupFolderCache(CantoRepository $repository): void
    {
        $this->cantoFolderCache->flushByTags([$repository->getCantoCacheTag()]);
        $repository->getFolderIdentifierTree(
            GetTreeRequest::SORT_BY_NAME,
            GetTreeRequest::SORT_DIRECTION_ASC
        );
    }
}
