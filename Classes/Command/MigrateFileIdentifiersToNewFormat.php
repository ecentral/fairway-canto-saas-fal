<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\CantoSaasFal\Command;

use Doctrine\DBAL\FetchMode;
use Fairway\CantoSaasFal\Resource\Driver\CantoDriver;
use Fairway\CantoSaasFal\Utility\CantoUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The old canto file identifier splitting scheme and id with a "#" did create problems with url requests.
 *      (especially visible in the file tree view, the "#" breaks the file context menu)
 * Due to this, a new splitting character has been introduced. This command migrates the old '#' to the new '||'.
 * @deprecated
 */
final class MigrateFileIdentifiersToNewFormat extends Command
{
    private StorageRepository $storageRepository;

    public function __construct(StorageRepository $storageRepository)
    {
        $this->storageRepository = $storageRepository;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Migrating file identifier to new format.');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class);
        assert($connection instanceof ConnectionPool);
        $builder = $connection->getQueryBuilderForTable('sys_file');
        foreach ($this->storageRepository->findByStorageType(CantoDriver::DRIVER_NAME) as $storage) {
            $uid = $storage->getUid();
            $statement = $builder->select('uid', 'identifier')
                ->from('sys_file')
                ->where($builder->expr()->eq('storage', $uid))
                ->execute();

            if (method_exists($statement, 'fetchAllAssociative')) {
                $result = $statement->fetchAllAssociative();
            } elseif (method_exists($statement, 'fetchAll')) {
                $result = $statement->fetchAll(FetchMode::ASSOCIATIVE);
            } else {
                continue;
            }

            foreach ($result as $item) {
                if (!str_contains($item['identifier'], '#')) {
                    continue;
                }
                $output->writeln('Updating File: ' . $item['identifier']);
                $newIdentifier = str_replace('#', CantoUtility::SPLIT_CHARACTER, $item['identifier']);
                $builder->update('sys_file')
                    ->set('identifier', $newIdentifier)
                    ->set('identifier_hash', $storage->hashFileIdentifier($newIdentifier))
                    ->set('sha1', $storage->hashFileByIdentifier($newIdentifier, 'sha1'))
                    ->where($builder->expr()->eq('uid', $item['uid']))
                    ->execute();
            }
        }
        return self::SUCCESS;
    }
}
