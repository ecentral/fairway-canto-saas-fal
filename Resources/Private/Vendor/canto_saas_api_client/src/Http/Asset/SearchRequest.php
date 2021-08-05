<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasApiClient\Http\Asset;

use Ecentral\CantoSaasApiClient\Http\RequestInterface;

class SearchRequest implements RequestInterface
{
    const APPROVAL_APPROVED = 'approved';
    const APPROVAL_PENDING = 'pending';
    const APPROVAL_EXPIRED = 'expired';
    const STORAGE_CLASS_STANDARD = 'standard';
    const STORAGE_CLASS_FREEZE = 'freeze';
    const SORT_DIRECTION_ASC = 'ascending';
    const SORT_DIRECTION_DESC = 'descending';
    const SORT_BY_TIME = 'time';
    const SORT_BY_NAME = 'name';
    const SORT_BY_SCHEME = 'scheme';
    const SORT_BY_OWNER = 'owner';
    const SORT_BY_SIZE = 'size';
    const SEARCH_IN_FIELD_FILENAME = 'filename';
    const SEARCH_IN_FIELD_DESCRIPTION = 'description';
    const SEARCH_IN_FIELD_COMMENT = 'comment';
    const SEARCH_IN_FIELD_KEYWORDS = 'keywords';
    const SEARCH_IN_FIELD_AUTHOR = 'author';
    const SEARCH_IN_FIELD_TAGS = 'tags';
    const OPERATOR_AND = 'and';
    const OPERATOR_OR = 'or';

    protected string $keyword = '';

    protected string $scheme = '';

    protected string $tags = '';

    protected string $tagsLiteral = '';

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

    protected string $storageClass = '';

    protected string $sortBy = self::SORT_BY_TIME;

    protected string $sortDirection = self::SORT_DIRECTION_DESC;

    protected int $start = 0;

    protected int $limit = 100;

    protected string $searchInField = '';

    protected string $operator = self::OPERATOR_AND;

    protected string $exactMatch = 'false';

    public function setKeyword(string $keyword): SearchRequest
    {
        $this->keyword = $keyword;
        return $this;
    }

    public function setScheme(string $scheme): SearchRequest
    {
        $this->scheme = $scheme;
        return $this;
    }

    public function setTags(string $tags): SearchRequest
    {
        $this->tags = $tags;
        return $this;
    }

    public function setTagsLiteral(string $tagsLiteral): SearchRequest
    {
        $this->tagsLiteral = $tagsLiteral;
        return $this;
    }

    public function setKeywords(string $keywords): SearchRequest
    {
        $this->keywords = $keywords;
        return $this;
    }

    public function setApproval(string $approval): SearchRequest
    {
        $this->approval = $approval;
        return $this;
    }

    public function setOwner(string $owner): SearchRequest
    {
        $this->owner = $owner;
        return $this;
    }

    /**
     * @param int $min Minimum file size in bytes
     * @param int $max Maximum file size in bytes
     */
    public function setFileSize(int $min, int $max): SearchRequest
    {
        $this->fileSize = $min . '..' . $max;
        return $this;
    }

    /**
     * @param int $start Unix timestamp
     * @param int $end Unix timestamp
     */
    public function setCreated(int $start, int $end): SearchRequest
    {
        $this->created = $start . '..' . $end;
        return $this;
    }

    /**
     * @param int $start Unix timestamp
     * @param int $end Unix timestamp
     */
    public function setCreatedTime(int $start, int $end): SearchRequest
    {
        $this->createdTime = $start . '..' . $end;
        return $this;
    }

    /**
     * @param int $start Unix timestamp
     * @param int $end Unix timestamp
     */
    public function setUploadedTime(int $start, int $end): SearchRequest
    {
        $this->uploadedTime = $start . '..' . $end;
        return $this;
    }

    /**
     * @param int $start Unix timestamp
     * @param int $end Unix timestamp
     */
    public function setLastModified(int $start, int $end): SearchRequest
    {
        $this->lastModified = $start . '..' . $end;
        return $this;
    }

    /**
     * @param int $min Minimum dimension in pixel
     * @param int $max Maximum dimension in pixel
     */
    public function setDimension(int $min, int $max): SearchRequest
    {
        $this->dimension = $min . '..' . $max;
        return $this;
    }

    /**
     * @param int $min Minimum resolution in DPI
     * @param int $max Maximum resolution in DPI
     */
    public function setResolution(int $min, int $max): SearchRequest
    {
        $this->resolution = $min . '..' . $max;
        return $this;
    }

    /**
     * @param string $orientation See ORIENTATION_* constants
     */
    public function setOrientation(string $orientation): SearchRequest
    {
        $this->orientation = $orientation;
        return $this;
    }

    public function setDuration(int $min, int $max): SearchRequest
    {
        $this->duration = $min . '..' . $max;
        return $this;
    }

    public function setPageNumber(int $min, int $max): SearchRequest
    {
        $this->pageNumber = $min . '..' . $max;
        return $this;
    }

    public function setStorageClass(string $storageClass): SearchRequest
    {
        $this->storageClass = $storageClass;
        return $this;
    }

    /**
     * See SORT_BY_* constants.
     */
    public function setSortBy(string $sortBy): SearchRequest
    {
        $this->sortBy = $sortBy;
        return $this;
    }

    /**
     * See SORT_DIRECTION_* constants.
     */
    public function setSortDirection(string $sortDirection): SearchRequest
    {
        $this->sortDirection = $sortDirection;
        return $this;
    }

    public function setLimit(int $limit): SearchRequest
    {
        $this->limit = $limit;
        return $this;
    }

    public function setStart(int $start): SearchRequest
    {
        $this->start = $start;
        return $this;
    }

    public function setSearchInField(string $searchInField): SearchRequest
    {
        $this->searchInField = $searchInField;
        return $this;
    }

    public function setOperator(string $operator): SearchRequest
    {
        $this->operator = $operator;
        return $this;
    }

    public function setExactMatch(bool $exactMatch): SearchRequest
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
            'tagsLiteral' => $this->tagsLiteral,
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
            'storageClass' => $this->storageClass,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
            'limit' => $this->limit,
            'start' => $this->start,
            'searchInField' => $this->searchInField,
            'operator' => $this->operator,
            'exactMatch' => $this->exactMatch
        ];
    }

    public function getPathVariables(): ?array
    {
        return null;
    }
}
