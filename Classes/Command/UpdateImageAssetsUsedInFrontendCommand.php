<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\CantoSaasFal\Command;

use Fairway\CantoSaasFal\Domain\Repository\FileReferenceRepository;
use Fairway\CantoSaasFal\Resource\Driver\CantoDriver;
use TYPO3\CMS\Core\Cache\CacheManager;
use Fairway\CantoSaasFal\Resource\Metadata\Extractor;
use Fairway\CantoSaasFal\Utility\CantoUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\SysLog\Action\Cache;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

final class UpdateImageAssetsUsedInFrontendCommand extends Command
{
    private Extractor $metadataExtractor;
    private StorageRepository $storageRepository;

    protected FrontendInterface $cantoFileCache;
    public function __construct(Extractor $metadataExtractor, StorageRepository $storageRepository)
    {
        $this->metadataExtractor = $metadataExtractor;
        $this->storageRepository = $storageRepository;
        parent::__construct();
    }
    public function injectCantoFileCache(FrontendInterface $cantoFileCache): void
    {
        $this->cantoFileCache = $cantoFileCache;
    }
    protected function configure(): void
    {
        $this->setDescription('Update Referenced Metadata Files for all used canto assets.');
        $this->setHelp(
            <<<'EOF'
This command will pull down all metadata and override it analog to the definition in the backend.
It will also delete all processed files to these files
EOF
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        $fileReferenceRepositry = GeneralUtility::makeInstance(FileReferenceRepository::class);
        $cacheManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(CacheManager::class);
        $cantoFileRepository = GeneralUtility::makeInstance(\Fairway\CantoSaasFal\Domain\Repository\FileRepository::class);


        assert($fileRepository instanceof FileRepository);

        $files = $fileRepository->findAll();
        $counter = 0;

        foreach ($files as $file) {
            assert($file instanceof File);
            $output->writeln('Working on File: ' . $file->getIdentifier() . ' - ' . $file->getName());

            //Check file references
            $fileReference = $fileReferenceRepositry->getFileReferenzesByFileUid($file->getUid());

            if ($file->getStorage()->getDriverType() !== CantoDriver::DRIVER_NAME || count($fileReference) == 0) {
                continue;
            }
            //We delete only referenced files
            try {
                //First delete cache
                $scheme = CantoUtility::getSchemeFromCombinedIdentifier($file->getIdentifier());
                $identifier = CantoUtility::getIdFromCombinedIdentifier($file->getIdentifier());
                $combinedIdentifier = CantoUtility::buildCombinedIdentifier($scheme, $identifier);
                $cacheIdentifier = sha1($combinedIdentifier);
                if ($this->cantoFileCache->has($cacheIdentifier)) {
                    //Clear old cache
                    $this->cantoFileCache->remove($cacheIdentifier);
                }

                $metaData = $this->metadataExtractor->extractMetaData($file);
                $fetchedDataForFile = $this->metadataExtractor->fetchDataForFile($file);
                if(isset($fetchedDataForFile['default'])) {
                    $newmtime = CantoUtility::buildTimestampFromCantoDate($fetchedDataForFile['default']['Date modified']);
                    if ($fetchedDataForFile && $newmtime > $file->getModificationTime()) {
                        $cantoFileRepository->updateModificationDate($file->getUid(),$newmtime);
                        $file->getMetaData()->add($metaData)->save();
                        $file->getForLocalProcessing(false);
                        $processedFileRepository = GeneralUtility::makeInstance(ProcessedFileRepository::class);
                        foreach ($processedFileRepository->findAllByOriginalFile($file) as $processedFile) {
                            $processedFile->delete(true);
                        }
                    }
                }
            } catch (\Exception $e) {
                $output->writeln('File ' . $file->getIdentifier() . ' failed: ' . $e->getMessage());
                continue;
            }
            if (++$counter > 1000) {
                $counter = 0;
                // to circumvent API limits we need to pause for 60s after processing a thousand requests
                sleep(60);
            }
        }
        //Clear frontend cache
        $cache = $cacheManager->getCache("pages");
        // Cache leeren
        if ($cache !== null) {
            $cache->flush();
        }
        return self::SUCCESS;
    }
}
