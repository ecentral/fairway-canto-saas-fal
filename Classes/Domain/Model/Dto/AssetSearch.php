<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Domain\Model\Dto;

use Fairway\CantoSaasApi\Http\Asset\SearchRequest;

class AssetSearch
{
    protected string $scheme = '';

    protected string $identifier = '';

    protected string $keyword = '';

    protected string $tags = '';

    protected string $categories = '';

    protected string $searchInField = '';

    protected int $start = 0;

    protected int $limit = 30;

    protected array $schemes = [];

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function setScheme(string $scheme): AssetSearch
    {
        $this->scheme = $scheme;
        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): AssetSearch
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function getKeyword(): string
    {
        return $this->keyword;
    }

    public function setKeyword(string $keyword): AssetSearch
    {
        $this->keyword = $keyword;
        return $this;
    }

    public function getTags(): string
    {
        return $this->tags;
    }

    public function setTags(string $tags): AssetSearch
    {
        $this->tags = $tags;
        return $this;
    }

    public function getCategories(): string
    {
        return $this->categories;
    }

    public function setCategories(string $categories): AssetSearch
    {
        $this->categories = $categories;
        return $this;
    }

    public function getSearchInField(): string
    {
        return $this->searchInField;
    }

    public function setSearchInField(string $searchInField): AssetSearch
    {
        $this->searchInField = $searchInField;
        return $this;
    }

    public function getStart(): int
    {
        return $this->start;
    }

    public function setStart(int $start): AssetSearch
    {
        $this->start = $start;
        return $this;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): AssetSearch
    {
        $this->limit = $limit;
        return $this;
    }

    public function getStatus(): string
    {
        return SearchRequest::APPROVAL_APPROVED;
    }

    public function setSchemes(array $schemes): AssetSearch
    {
        $this->schemes = $schemes;
        return $this;
    }

    public function getSchemes(): array
    {
        return $this->schemes;
    }
}
