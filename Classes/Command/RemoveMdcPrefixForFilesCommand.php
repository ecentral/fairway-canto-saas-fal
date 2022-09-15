<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\CantoSaasFal\Command;

use Fairway\CantoSaasFal\Resource\Driver\CantoDriver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * todo: remove this command before public release
 * @internal
 */
final class RemoveMdcPrefixForFilesCommand extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Remove mdc::-Prefix from all mdc files');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        assert($fileRepository instanceof FileRepository);
        $files = $fileRepository->findAll();
        foreach ($files as $file) {
            assert($file instanceof File);
            if ($file->getStorage()->getDriverType() !== CantoDriver::DRIVER_NAME) {
                continue;
            }
            $indexer = GeneralUtility::makeInstance(Indexer::class, $file->getStorage());
            if (str_starts_with($file->getIdentifier(), 'mdc::')) {
                $file->setIdentifier(str_replace('mdc::', '', $file->getIdentifier()));
                $file->updateProperties(['identifier' => $file->getIdentifier()]);
                $indexer->updateIndexEntry($file);
            }
        }
        return self::SUCCESS;
    }
}
