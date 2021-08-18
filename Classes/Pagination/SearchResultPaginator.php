<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Pagination;

use Ecentral\CantoSaasApiClient\Endpoint\Authorization\NotAuthorizedException;
use Ecentral\CantoSaasApiClient\Http\InvalidResponseException;
use Ecentral\CantoSaasFal\Domain\Model\Dto\AssetSearch;
use Ecentral\CantoSaasFal\Domain\Model\Dto\AssetSearchResponse;
use Ecentral\CantoSaasFal\Resource\Repository\CantoRepository;
use TYPO3\CMS\Core\Pagination\AbstractPaginator;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class SearchResultPaginator extends AbstractPaginator
{
    private AssetSearchResponse $searchResult;

    public function __construct(
        AssetSearch $search,
        CantoRepository $cantoRepository,
        int $currentPageNumber = 1,
        int $itemsPerPage = 30
    ) {
        $this->setCurrentPageNumber($currentPageNumber);
        $this->setItemsPerPage($itemsPerPage);
        $this->fetchItems($search, $cantoRepository, $currentPageNumber, $itemsPerPage);
        $this->updateInternalState();
    }

    /**
     * @return array
     */
    public function getPaginatedItems(): array
    {
        return $this->searchResult->getResults();
    }

    public function getNumberOfAllItems(): int
    {
        return $this->getTotalAmountOfItems();
    }

    protected function fetchItems(
        AssetSearch $search,
        CantoRepository $cantoRepository,
        int $currentPageNumber = 1,
        int $itemsPerPage = 30
    ): void {
        if ($search->getScheme() !== '' && $search->getIdentifier() !== '') {
            $this->searchResult = $this->fetchItemByIdentifier($search, $cantoRepository);
            return;
        }

        $offset = (int)($itemsPerPage * ($currentPageNumber - 1));
        $search->setLimit($itemsPerPage)->setStart($offset);

        try {
            $this->searchResult = $cantoRepository->search($search);
        } catch (NotAuthorizedException | InvalidResponseException $e) {
            $this->searchResult = GeneralUtility::makeInstance(AssetSearchResponse::class);
        }
    }

    protected function fetchItemByIdentifier(AssetSearch $search, CantoRepository $cantoRepository): AssetSearchResponse
    {
        $response = GeneralUtility::makeInstance(AssetSearchResponse::class);
        $result = $cantoRepository->getFileDetails($search->getScheme(), $search->getIdentifier());
        $allowedFileExtensions = explode('|.', trim($search->getKeyword(), '.'));
        if (is_array($result)
            && isset($result['metadata']['File Type Extension'])
            && in_array($result['metadata']['File Type Extension'], $allowedFileExtensions)) {
            $response->setResults([$result])
                ->setFound(1);
        }
        return $response;
    }

    /**
     * Results are fetched in SearchResultPaginator::fetchItems()
     */
    protected function updatePaginatedItems(int $itemsPerPage, int $offset): void
    {
    }

    protected function getTotalAmountOfItems(): int
    {
        return $this->searchResult->getFound();
    }

    protected function getAmountOfItemsOnCurrentPage(): int
    {
        return count($this->searchResult->getResults());
    }
}
