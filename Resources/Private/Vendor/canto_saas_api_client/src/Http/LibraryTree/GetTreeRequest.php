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

class GetTreeRequest implements RequestInterface
{
    const SORT_DIRECTION_ASC = 'ascending';
    const SORT_DIRECTION_DESC = 'descending';
    const SORT_BY_TIME = 'time';
    const SORT_BY_NAME = 'name';
    const SORT_BY_SCHEME = 'scheme';
    const SORT_BY_OWNER = 'owner';
    const SORT_BY_SIZE = 'size';

    protected string $folderId;

    protected string $sortBy = self::SORT_BY_TIME;

    protected string $sortDirection = self::SORT_DIRECTION_ASC;

    protected int $layer = -1;

    public function __construct(string $folderId = '')
    {
        $this->folderId = $folderId;
    }

    /**
     * See SORT_BY_* constants.
     */
    public function setSortBy(string $sortBy): GetTreeRequest
    {
        $this->sortBy = $sortBy;
        return $this;
    }

    /**
     * See SORT_DIRECTION_* constants.
     */
    public function setSortDirection(string $sortDirection): GetTreeRequest
    {
        $this->sortDirection = $sortDirection;
        return $this;
    }

    public function setLayer(int $layer): GetTreeRequest
    {
        $this->layer = $layer;
        return $this;
    }

    public function getQueryParams(): ?array
    {
        return [
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
            'layer' => $this->layer
        ];
    }

    public function getPathVariables(): ?array
    {
        if ($this->folderId !== '') {
            return [
                $this->folderId,
            ];
        }
        return null;
    }
}
