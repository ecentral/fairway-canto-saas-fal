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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
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
            'isMdcEnabled' => $storage->getConfiguration()['mdcEnabled'] ?? false,
        ]);

        $response = new Response();
        $response->getBody()->write($view->render());
        return $response;
    }

    public function importFile(ServerRequestInterface $request): ResponseInterface
    {
        return $this->buildFileFetchingResponse($request);
    }

    public function importCdn(ServerRequestInterface $request): ResponseInterface
    {
        return $this->buildFileFetchingResponse($request, true);
    }

    private function buildFileFetchingResponse(ServerRequestInterface $request, bool $cdn = false): ResponseInterface
    {
        $storageUid = (int)($request->getQueryParams()['storageUid'] ?? 0);
        $scheme = $request->getQueryParams()['scheme'] ?? '';
        $identifier = $request->getQueryParams()['identifier'] ?? '';
        $storage = $this->getCantoStorageByUid($storageUid);

        if ($scheme && $identifier) {
            if ($cdn) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable('sys_file')->createQueryBuilder();
                $result = $queryBuilder
                    ->select('identifier')
                    ->from('sys_file')
                    ->where($queryBuilder->expr()->eq(
                        'identifier',
                        $queryBuilder->createNamedParameter(
                            CantoUtility::buildCombinedIdentifier($scheme, $identifier, true)
                        )
                    ))
                    ->execute()
                    ->fetchAllAssociative();
                if (count($result) > 0) {
                    // toggling the mdc on/off depending on whether the file already exists or not
                    // this would be a better experience in the browser itself, disabling every button that is duplicated
                    // but that would probably have a huge performance impact, thus we do the switch here
                    // we only care about duplicates, when we are using mdc, as we dont have to download files impacts for cdn files
                    $cdn = false;
                }
            }


            $combinedFileIdentifier = CantoUtility::buildCombinedIdentifier($scheme, $identifier, $cdn);

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
        $search = new AssetSearch();
        $searchType = (string)$request->getQueryParams()['search']['type'] ?? '';
        $allowedFileExtensions = $request->getQueryParams()['allowedFileExtensions'] ?? '';
        if ($allowedFileExtensions) {
            $search->setKeyword(implode('|', array_map(
                static fn (string $fileExtension) => '.' . trim($fileExtension),
                explode(',', $allowedFileExtensions)
            )));
        }

        // TODO We cannot use keyword search and file extension filter because of missing support for logical grouping.
        switch ($searchType) {
            case 'identifier':
                $identifier = (string)$request->getQueryParams()['search']['identifier'] ?? '';
                $scheme = (string)$request->getQueryParams()['search']['scheme'] ?? '';
                $search->setIdentifier($identifier);
                $search->setScheme($scheme);
                break;
            case 'categories':
                $searchQuery = (string)$request->getQueryParams()['search']['query'] ?? '';
                $search->setCategories($searchQuery);
                break;
            case 'tags':
                $searchQuery = (string)$request->getQueryParams()['search']['query'] ?? '';
                $search->setTags($searchQuery);
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
