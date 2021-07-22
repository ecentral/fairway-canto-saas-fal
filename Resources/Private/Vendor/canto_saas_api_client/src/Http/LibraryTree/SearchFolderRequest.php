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

class SearchFolderRequest implements RequestInterface
{
    const SORT_DIRECTION_ASC = 'ascending';
    const SORT_DIRECTION_DESC = 'descending';
    const SORT_BY_TIME = 'time';
    const SORT_BY_NAME = 'name';
    const SORT_BY_SCHEME = 'scheme';
    const SORT_BY_OWNER = 'owner';
    const SORT_BY_SIZE = 'size';
    const ORIENTATION_LANDSCAPE = 'landscape';
    const ORIENTATION_PORTRAIT = 'portrait';
    const ORIENTATION_SQUARE= 'square';

    protected string $folderId;

    protected string $keyword = '';

    protected string $scheme = '';

    protected string $tags = '';

    protected string $keywords = '';

    protected string $approval = '';

    protected string $owner = '';

    protected string $fileSize = '';

    protected string $created = '';

    protected string $createdTime = '';

    protected string $uploadedTime = '';

    protected string $lastModified = '';

    protected string $dimension = '';

    protected string $resolution = '';

    protected string $orientation = '';

    protected string $duration = '';

    protected string $pageNumber = '';

    protected string $sortBy = self::SORT_BY_TIME;

    protected string $sortDirection = self::SORT_DIRECTION_DESC;

    protected int $limit = 100;

    protected int $start = 0;

    protected string $exactMatch = 'false';

    public function __construct(string $folderId)
    {
        $this->folderId = $folderId;
    }

    public function setKeyword(string $keyword): SearchFolderRequest
    {
        $this->keyword = $keyword;
        return $this;
    }

    public function setScheme(string $scheme): SearchFolderRequest
    {
        $this->scheme = $scheme;
        return $this;
    }

    public function setTags(string $tags): SearchFolderRequest
    {
        $this->tags = $tags;
        return $this;
    }

    public function setKeywords(string $keywords): SearchFolderRequest
    {
        $this->keywords = $keywords;
        return $this;
    }

    public function setApproval(string $approval): SearchFolderRequest
    {
        $this->approval = $approval;
        return $this;
    }

    public function setOwner(string $owner): SearchFolderRequest
    {
        $this->owner = $owner;
        return $this;
    }

    /**
     * @param int $min Minimum file size in bytes
     * @param int $max Maximum file size in bytes
     */
    public function setFileSize(int $min, int $max): SearchFolderRequest
    {
        $this->fileSize = $min . '..' . $max;
        return $this;
    }

    /**
     * @param int $start Unix timestamp
     * @param int $end Unix timestamp
     */
    public function setCreated(int $start, int $end): SearchFolderRequest
    {
        $this->created = $start . '..' . $end;
        return $this;
    }

    /**
     * @param int $start Unix timestamp
     * @param int $end Unix timestamp
     */
    public function setCreatedTime(int $start, int $end): SearchFolderRequest
    {
        $this->createdTime = $start . '..' . $end;
        return $this;
    }

    /**
     * @param int $start Unix timestamp
     * @param int $end Unix timestamp
     */
    public function setUploadedTime(int $start, int $end): SearchFolderRequest
    {
        $this->uploadedTime = $start . '..' . $end;
        return $this;
    }

    /**
     * @param int $start Unix timestamp
     * @param int $end Unix timestamp
     */
    public function setLastModified(int $start, int $end): SearchFolderRequest
    {
        $this->lastModified = $start . '..' . $end;
        return $this;
    }

    /**
     * @param int $min Minimum dimension in pixel
     * @param int $max Maximum dimension in pixel
     */
    public function setDimension(int $min, int $max): SearchFolderRequest
    {
        $this->dimension = $min . '..' . $max;
        return $this;
    }

    /**
     * @param int $min Minimum resolution in DPI
     * @param int $max Maximum resolution in DPI
     */
    public function setResolution(int $min, int $max): SearchFolderRequest
    {
        $this->resolution = $min . '..' . $max;
        return $this;
    }

    /**
     * @param string $orientation See ORIENTATION_* constants
     */
    public function setOrientation(string $orientation): SearchFolderRequest
    {
        $this->orientation = $orientation;
        return $this;
    }

    public function setDuration(int $min, int $max): SearchFolderRequest
    {
        $this->duration = $min . '..' . $max;
        return $this;
    }

    public function setPageNumber(int $min, int $max): SearchFolderRequest
    {
        $this->pageNumber = $min . '..' . $max;
        return $this;
    }

    /**
     * See SORT_BY_* constants.
     */
    public function setSortBy(string $sortBy): SearchFolderRequest
    {
        $this->sortBy = $sortBy;
        return $this;
    }

    /**
     * See SORT_DIRECTION_* constants.
     */
    public function setSortDirection(string $sortDirection): SearchFolderRequest
    {
        $this->sortDirection = $sortDirection;
        return $this;
    }

    public function setLimit(int $limit): SearchFolderRequest
    {
        $this->limit = $limit;
        return $this;
    }

    public function setStart(int $start): SearchFolderRequest
    {
        $this->start = $start;
        return $this;
    }

    public function setExactMatch(bool $exactMatch): SearchFolderRequest
    {
        $this->exactMatch = $exactMatch ? 'true' : 'false';
        return $this;
    }

    public function getQueryParams(): ?array
    {
        return [
            'keyword' => $this->keyword,
            'scheme' => $this->scheme,
            'tags' => $this->tags,
            'keywords' => $this->keywords,
            'approval' => $this->approval,
            'owner' => $this->owner,
            'fileSize' => $this->fileSize,
            'created' => $this->created,
            'createdTime' => $this->createdTime,
            'uploadedTime' => $this->uploadedTime,
            'lastModified' => $this->lastModified,
            'dimension' => $this->dimension,
            'resolution' => $this->resolution,
            'orientation' => $this->orientation,
            'duration' => $this->duration,
            'pageNumber' => $this->pageNumber,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
            'limit' => $this->limit,
            'start' => $this->start,
            'exactMatch' => $this->exactMatch
        ];
    }

    public function getPathVariables(): ?array
    {
        return [
            $this->folderId,
        ];
    }
}
