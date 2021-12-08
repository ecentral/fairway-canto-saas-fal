<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Resource\Event;

final class MetadataWebhookEvent
{
    private string $scheme;
    private string $album;
    private string $displayName;
    private string $id;

    public function __construct(string $scheme, string $album, string $displayName, string $id)
    {
        $this->scheme = $scheme;
        $this->album = $album;
        $this->displayName = $displayName;
        $this->id = $id;
    }

    public function getAlbum(): string
    {
        return $this->album;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param array{secure_token: string, scheme: string, album: string, displayname: string, id: string} $json
     * @return self
     */
    public static function fromJsonArray(array $json): self
    {
        return new self($json['scheme'], $json['album'], $json['displayname'], $json['id']);
    }
}
