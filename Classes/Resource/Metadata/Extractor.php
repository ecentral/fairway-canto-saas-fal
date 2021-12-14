<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Resource\Metadata;

use Ecentral\CantoSaasApiClient\Endpoint\Authorization\AuthorizationFailedException;
use Ecentral\CantoSaasFal\Resource\Driver\CantoDriver;
use Ecentral\CantoSaasFal\Resource\Event\AfterMetaDataExtractionEvent;
use Ecentral\CantoSaasFal\Resource\Repository\CantoRepository;
use Ecentral\CantoSaasFal\Utility\CantoUtility;
use JsonException;
use TYPO3\CMS\Core\Category\Collection\CategoryCollection;
use TYPO3\CMS\Core\Collection\CollectionInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\ExtractorInterface;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class Extractor implements ExtractorInterface
{
    protected CantoRepository $cantoRepository;
    private EventDispatcher $dispatcher;

    public function __construct(CantoRepository $cantoRepository, EventDispatcher $dispatcher)
    {
        $this->cantoRepository = $cantoRepository;
        $this->dispatcher = $dispatcher;
    }

    public function getFileTypeRestrictions(): array
    {
        return [];
    }

    public function getDriverRestrictions(): array
    {
        return [];
    }

    public function getPriority(): int
    {
        return 10;
    }

    public function getExecutionPriority(): int
    {
        return 10;
    }

    public function canProcess(File $file): bool
    {
        return $file->getStorage()->getDriverType() === CantoDriver::DRIVER_NAME;
    }

    /**
     * @throws AuthorizationFailedException
     */
    public function extractMetaData(File $file, array $previousExtractedData = []): array
    {
        $configuration = $file->getStorage()->getConfiguration();
        $this->cantoRepository->initialize($file->getStorage()->getUid(), $configuration);
        $fileData = $this->fetchDataForFile($file);
        if ($fileData === null) {
            return $previousExtractedData;
        }
        $metadata = array_replace(
            $previousExtractedData,
            [
                'width' => (int)($fileData['width'] ?? 0),
                'height' => (int)($fileData['height'] ?? 0),
                'pages' => (int)$fileData['default']['Pages'],
                'creator' => $fileData['default']['Author'],
                'creator_tool' => $fileData['default']['Creation Tool'],
                'copyright' => $fileData['default']['Copyright'],
            ]
        );
        $mapping = [];
        if (array_key_exists('metadataMapping', $configuration)) {
            try {
                $mapping = json_decode($configuration['metadataMapping'], true, 512, JSON_THROW_ON_ERROR);
                $metadata = $this->applyMappedMetaData($mapping, $metadata, $fileData);
            } catch (JsonException $exception) {
                # todo: add mapping logging
            }
        }
        $event = new AfterMetaDataExtractionEvent($metadata, $mapping, $fileData);
        $metadata = $this->dispatcher->dispatch($event)->getMetadata();
        if (array_key_exists('categoryMapping', $configuration)) {
            try {
                $mapping = json_decode($configuration['categoryMapping'], true, 512, JSON_THROW_ON_ERROR);
                $categoryData = $this->applyMappedMetaData($mapping, [], $fileData);
                $categories = $this->buildCategoryTree($categoryData);
                GeneralUtility::makeInstance(PersistenceManager::class)->persistAll();
                $metadataObject = GeneralUtility::makeInstance(MetaDataRepository::class)->findByFileUid($file->getUid());
                if ($metadataObject) {
                    foreach ($categories as $category) {
                        $categoryCollection = CategoryCollection::load($category->getUid(), true, 'sys_file_metadata', 'categories');
                        assert($categoryCollection instanceof CategoryCollection);
                        $categoryCollection->add([
                            'uid' => $metadataObject['uid'],
                        ]);
                        $categoryCollection->persist();
                    }
                }
            } catch (JsonException $exception) {
                # todo: add mapping logging
            }
        }
        return $metadata;
    }

    protected function fetchDataForFile(File $file): ?array
    {
        $scheme = CantoUtility::getSchemeFromCombinedIdentifier($file->getIdentifier());
        $identifier = CantoUtility::getIdFromCombinedIdentifier($file->getIdentifier());
        return $this->cantoRepository->getFileDetails($scheme, $identifier);
    }

    private function applyMappedMetaData(array $mapping, array $metadata, array $fileData): array
    {
        foreach ($mapping as $metadataKey => $fileDataKey) {
            $fileDataKeyArray = explode('->', $fileDataKey);
            $value = $this->extractFromMetadata($fileData, $fileDataKeyArray);
            if ($value) {
                $metadata[$metadataKey] = $value;
            }
        }

        return $metadata;
    }

    private function extractFromMetadata(array $metadata, array $fileKey)
    {
        if (!$metadata || !$fileKey) {
            return null;
        }
        $key = array_shift($fileKey);
        if (count($fileKey) === 0) {
            if ($metadata[$key] ?? null) {
                return $metadata[$key];
            }
            foreach (['metadata', 'additional', 'default'] as $metadataElement) {
                $value = $metadata[$metadataElement][$key] ?? null;
                if ($value) {
                    return $value;
                }
            }
        }
        return $this->extractFromMetadata($metadata[$key] ?? [], $fileKey);
    }

    /**
     * @param array $json
     * @param Category|null $parent
     * @param CategoryRepository|null $categoryRepository
     * @return Category[]
     */
    private function buildCategoryTree(array $json, Category $parent = null, CategoryRepository $categoryRepository = null): array
    {
        $categoryRepository ??= GeneralUtility::makeInstance(CategoryRepository::class);
        $categories = [];
        foreach ($json as $title => $children) {
            $category = $parent;
            if (is_string($title)) {
                $category = $this->addCategory($title, $parent, $categoryRepository);
                $categories[] = $category;
            }
            if (is_array($children) && $parent !== null) {
                $categories = [...$categories, ...$this->buildCategoryTree($children, $category, $categoryRepository)];
            }
            if (is_string($children)) {
                $categories[] = $this->addCategory($children, $category, $categoryRepository);
            }
        }
        return $categories;
    }

    private function addCategory(string $title, ?Category $parent, CategoryRepository $repository): Category
    {
        assert(is_callable([$repository, 'findByTitle']));
        $category = $repository->findByTitle($title)->toArray()[0] ?? null;
        if ($category === null) {
            $category = new Category();
            $category->setDescription('Canto-Generated-Category');
            $category->setTitle($title);
            if ($parent) {
                $category->setParent($parent);
            }
            $repository->add($category);
        }
        return $category;
    }
}
