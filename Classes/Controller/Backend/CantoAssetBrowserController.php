<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Controller\Backend;

use Ecentral\CantoSaasApiClient\Endpoint\Authorization\AuthorizationFailedException;
use Ecentral\CantoSaasFal\Domain\Model\Dto\AssetSearch;
use Ecentral\CantoSaasFal\Pagination\SearchResultPaginator;
use Ecentral\CantoSaasFal\Resource\Driver\CantoDriver;
use Ecentral\CantoSaasFal\Resource\NoCantoStorageException;
use Ecentral\CantoSaasFal\Resource\Repository\CantoRepository;
use Ecentral\CantoSaasFal\Resource\Repository\Exception\InvalidSearchTypeException;
use Ecentral\CantoSaasFal\Utility\CantoUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class CantoAssetBrowserController
{
    protected StorageRepository $storageRepository;

    public function __construct(StorageRepository $storageRepository)
    {
        $this->storageRepository = $storageRepository;
    }

    /**
     * @throws NoCantoStorageException
     * @throws AuthorizationFailedException
     * @throws InvalidSearchTypeException
     */
    public function search(ServerRequestInterface $request): ResponseInterface
    {
        $storageUid = (int)$request->getQueryParams()['storageUid'];
        $page = max((int)$request->getQueryParams()['page'], 1);
        $storage = $this->getCantoStorageByUid($storageUid);
        $cantoRepository = $this->getCantoRepository($storage);
        $search = $this->buildAssetSearchObject($request);
        $paginator = new SearchResultPaginator($search, $cantoRepository, $page);

        $view = $this->initializeView();
        $view->setTemplate('SearchResults');
        $view->assignMultiple([
            'results' => $paginator,
            'pagination' => new SimplePagination($paginator),
            'queryParams' => $request->getQueryParams(),
        ]);

        $response = new Response();
        $response->getBody()->write($view->render());
        return $response;
    }

    /**
     * @throws NoCantoStorageException
     */
    public function importFile(ServerRequestInterface $request): ResponseInterface
    {
        $scheme = $request->getQueryParams()['scheme'] ?? '';
        $identifier = $request->getQueryParams()['identifier'] ?? '';
        $storageUid = (int)$request->getQueryParams()['storageUid'] ?? 0;
        $storage = $this->getCantoStorageByUid($storageUid);
        if (strlen($scheme) > 0 && strlen($identifier) > 0) {
            $combinedFileIdentifier = CantoUtility::buildCombinedIdentifier($scheme, $identifier);
            $file = $storage->getFile($combinedFileIdentifier);
            if ($file instanceof File) {
                return new JsonResponse([
                    'fileUid' => $file->getUid(),
                    'fileName' => $file->getName(),
                ]);
            }
        }
        // Todo Add error message what went wrong.
        return new Response(null, 400);
    }

    /**
     * @throws InvalidSearchTypeException
     */
    protected function buildAssetSearchObject(ServerRequestInterface $request): AssetSearch
    {
        $search = GeneralUtility::makeInstance(AssetSearch::class);
        $allowedFileExtensions = array_map(
            function (string $fileExtension) {
                return '.' . trim($fileExtension);
            },
            explode(',', $request->getQueryParams()['allowedFileExtensions'] ?? '')
        );
        $searchType = (string)$request->getQueryParams()['search']['type'] ?? '';

        // TODO We cannot use keyword search and file extension filter because of missing support for logical grouping.
        switch ($searchType) {
            case 'identifier':
                $identifier = (string)$request->getQueryParams()['search']['identifier'] ?? '';
                $scheme = (string)$request->getQueryParams()['search']['scheme'] ?? '';
                $search->setIdentifier($identifier);
                $search->setScheme($scheme);
                $search->setKeyword(implode('|', $allowedFileExtensions));
                break;
            case 'categories':
                $searchQuery = (string)$request->getQueryParams()['search']['query'] ?? '';
                $search->setCategories($searchQuery);
                $search->setKeyword(implode('|', $allowedFileExtensions));
                break;
            case 'tags':
                $searchQuery = (string)$request->getQueryParams()['search']['query'] ?? '';
                $search->setTags($searchQuery);
                $search->setKeyword(implode('|', $allowedFileExtensions));
                break;
            default:
                throw new InvalidSearchTypeException(
                    sprintf('Invalid search type %s given.', $searchType),
                    1629119913
                );
        }

        return $search;
    }

    /**
     * @throws NoCantoStorageException
     */
    protected function getCantoStorageByUid(int $uid): ResourceStorage
    {
        $storage = $this->storageRepository->findByUid($uid);
        if ($storage === null || $storage->getDriverType() !== CantoDriver::DRIVER_NAME) {
            throw new NoCantoStorageException('The given storage is not a canto storage.', 1628166504);
        }
        return $storage;
    }

    /**
     * @throws AuthorizationFailedException
     */
    protected function getCantoRepository(ResourceStorage $storage): CantoRepository
    {
        $cantoRepository = GeneralUtility::makeInstance(CantoRepository::class);
        $cantoRepository->initialize($storage->getUid(), $storage->getConfiguration());
        return $cantoRepository;
    }

    protected function initializeView(): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths([
            100 => 'EXT:canto_saas_fal/Resources/Private/Layouts/'
        ]);
        $view->setPartialRootPaths([
            100 => 'EXT:canto_saas_fal/Resources/Private/Partials/',
        ]);
        $view->setTemplateRootPaths([
            100 => 'EXT:canto_saas_fal/Resources/Private/Templates/CantoAssetBrowser/Ajax/'
        ]);
        return $view;
    }
}