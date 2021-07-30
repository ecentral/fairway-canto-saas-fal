<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasApiClient\Http\LibraryTree;

use Ecentral\CantoSaasApiClient\Http\InvalidResponseException;
use Ecentral\CantoSaasApiClient\Http\Response;
use Psr\Http\Message\ResponseInterface;

class SearchFolderResponse extends Response
{
    protected array $facets;

    protected array $results;

    protected int $limit;

    protected int $found;

    protected string $sortBy;

    protected string $sortDirection;

    protected string $matchExpr;

    /**
     * @throws InvalidResponseException
     */
    public function __construct(ResponseInterface $response)
    {
        $responseData = $this->parseResponse($response);

        $this->facets = $responseData['facets'];
        $this->results = $responseData['results'] ?? [];
        $this->limit = $responseData['limit'];
        $this->found = $responseData['found'] ?? 0;
        $this->sortBy = $responseData['sortBy'];
        $this->sortDirection = $responseData['sortDirection'];
        $this->matchExpr = $responseData['matchExpr'];
    }

    public function getFacets(): array
    {
        return $this->facets;
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getFound(): int
    {
        return $this->found;
    }

    public function getSortBy(): string
    {
        return $this->sortBy;
    }

    public function getSortDirection(): string
    {
        return $this->sortDirection;
    }

    public function getMatchExpr(): string
    {
        return $this->matchExpr;
    }
}
