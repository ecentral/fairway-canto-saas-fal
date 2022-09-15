<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\CantoSaasFal\Domain\Model\Dto;

class AssetSearchResponse
{
    protected array $results = [];

    protected int $found = 0;

    public function getResults(): array
    {
        return $this->results;
    }

    public function setResults(array $results): AssetSearchResponse
    {
        $this->results = $results;
        return $this;
    }

    public function getFound(): int
    {
        return $this->found;
    }

    public function setFound(int $found): AssetSearchResponse
    {
        $this->found = $found;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'found' => $this->found,
            'results' => $this->results,
        ];
    }
}
