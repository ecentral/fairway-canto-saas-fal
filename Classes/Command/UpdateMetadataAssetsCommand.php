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
use Ecentral\CantoSaasFal\Resource\Metadata\Extractor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class UpdateMetadataAssetsCommand extends Command
{
    private Extractor $metadataExtractor;
    private StorageRepository $storageRepository;

    public function __construct(Extractor $metadataExtractor, StorageRepository $storageRepository)
    {
        $this->metadataExtractor = $metadataExtractor;
        $this->storageRepository = $storageRepository;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Update Metadata for all integrated canto assets.');
        $this->setHelp(
            <<<'EOF'
This command will pull down all metadata and override it analog to the definition in the backend.
It will also delete all processed files to these files
EOF
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $storages = $this->storageRepository->findByStorageType(CantoDriver::DRIVER_NAME);
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        assert($fileRepository instanceof FileRepository);
        $files = $fileRepository->findAll();
        $counter = 0;
        foreach ($files as $file) {
            assert($file instanceof File);
            $output->writeln('Working on File: ' . $file->getIdentifier() . ' - ' . $file->getName());
            if ($file->getStorage()->getDriverType() !== CantoDriver::DRIVER_NAME) {
                continue;
            }
            $metaData = $this->metadataExtractor->extractMetaData($file);
            if ($metaData) {
                $file->getMetaData()->add($metaData)->save();
                $file->getForLocalProcessing(true);
                $processedFileRepository = GeneralUtility::makeInstance(ProcessedFileRepository::class);
                foreach ($processedFileRepository->findAllByOriginalFile($file) as $processedFile) {
                    $processedFile->delete(true);
                }
            }
            if (++$counter > 1000) {
                $counter = 0;
                // to circumvent API limits we need to pause for 60s after processing a thousand requests
                sleep(60);
            }
        }
        return self::SUCCESS;
    }
}
