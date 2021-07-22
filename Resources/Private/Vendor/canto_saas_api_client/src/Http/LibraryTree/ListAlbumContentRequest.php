<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasApiClient\Http\LibraryTree;

use Ecentral\CantoSaasApiClient\Http\RequestInterface;

class ListAlbumContentRequest implements RequestInterface
{
    const SORT_DIRECTION_ASC = 'ascending';
    const SORT_DIRECTION_DESC = 'descending';
    const SORT_BY_TIME = 'time';
    const SORT_BY_NAME = 'name';
    const SORT_BY_SCHEME = 'scheme';
    const SORT_BY_OWNER = 'owner';
    const SORT_BY_SIZE = 'size';

    protected string $albumId;

    protected string $sortBy = self::SORT_BY_TIME;

    protected string $sortDirection = self::SORT_DIRECTION_DESC;

    protected int $limit = 100;

    protected int $start = 0;

    public function __construct(string $albumId)
    {
        $this->albumId = $albumId;
    }

    /**
     * See SORT_BY_* constants.
     */
    public function setSortBy(string $sortBy): ListAlbumContentRequest
    {
        $this->sortBy = $sortBy;
        return $this;
    }

    /**
     * See SORT_DIRECTION_* constants.
     */
    public function setSortDirection(string $sortDirection): ListAlbumContentRequest
    {
        $this->sortDirection = $sortDirection;
        return $this;
    }

    public function setLimit(int $limit): ListAlbumContentRequest
    {
        $this->limit = $limit;
        return $this;
    }

    public function setStart(int $start): ListAlbumContentRequest
    {
        $this->start = $start;
        return $this;
    }

    public function getQueryParams(): ?array
    {
        return [
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
            'limit' => $this->limit,
            'start' => $this->start,
        ];
    }

    public function getPathVariables(): ?array
    {
        return [
            $this->albumId,
        ];
    }
}
