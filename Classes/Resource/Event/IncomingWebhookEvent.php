<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Resource\Event;

final class IncomingWebhookEvent
{
    public const METADATA_UPDATE = 'metadata_update';
    public const ASSET_VERSION_UPDATE = 'new_asset_version';
    public const ASSET_DELETION = 'asset_deletion';
    public const CUSTOM = 'custom';

    private string $scheme;
    private string $album;
    private string $displayName;
    private string $id;
    private string $type;
    private string $token = '';

    public function __construct(string $scheme, string $album, string $displayName, string $id, string $type)
    {
        $this->scheme = $scheme;
        $this->album = $album;
        $this->displayName = $displayName;
        $this->id = $id;
        $this->type = $type;
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    /**
     * Token is only set, if the event was not identified and thus marked as a custom IncomingWebhookEvent
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param array{secure_token: string, scheme: string, album: string, displayname: string, id: string} $json
     * @param string|self::* $type
     * @return self
     */
    public static function fromJsonArray(array $json, string $type): self
    {
        return new self($json['scheme'], $json['album'], $json['displayname'], $json['id'], $type);
    }
}
