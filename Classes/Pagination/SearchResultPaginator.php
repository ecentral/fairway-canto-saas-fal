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
        $offset = (int)($itemsPerPage * ($currentPageNumber - 1));
        $search->setLimit($itemsPerPage)->setStart($offset);

        try {
            $this->searchResult = $cantoRepository->search($search);
        } catch (NotAuthorizedException | InvalidResponseException $e) {
            $this->searchResult = GeneralUtility::makeInstance(AssetSearchResponse::class);
        }
    }

    /**
     * Results are fetched in SearchResultPaginator::fetchItems()
     */
    protected function updatePaginatedItems(int $itemsPerPage, int $offset): void
    {
    }

    /**
     * @return int
     */
    protected function getTotalAmountOfItems(): int
    {
        return $this->searchResult->getFound();
    }

    /**
     * @return int
     */
    protected function getAmountOfItemsOnCurrentPage(): int
    {
        return count($this->searchResult->getResults());
    }
}
