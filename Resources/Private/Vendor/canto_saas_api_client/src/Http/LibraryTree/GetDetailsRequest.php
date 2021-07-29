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

class GetDetailsRequest implements RequestInterface
{
    const TYPE_FOLDER = 'folder';
    const TYPE_ALBUM = 'album';

    protected string $folderId;

    protected string $type;

    public function __construct(string $folderId, string $type = self::TYPE_FOLDER)
    {
        $this->folderId = $folderId;
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getQueryParams(): ?array
    {
        return null;
    }

    public function getPathVariables(): ?array
    {
        return [
            $this->folderId,
        ];
    }
}
