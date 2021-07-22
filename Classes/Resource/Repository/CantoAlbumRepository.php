<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Resource\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SingletonInterface;

class CantoAlbumRepository implements SingletonInterface
{
    protected string $table;

    protected ConnectionPool $connectionPool;

    public function __construct(string $table, ConnectionPool $connectionPool)
    {
        $this->table = $table;
        $this->connectionPool = $connectionPool;
    }

    public function updateAlbumsForFileUid(int $fileUid, array $albumIdentifiers): void
    {
        // Delete existing relations before inserting new ones.
        $this->deleteByFileUid($fileUid);

        foreach ($albumIdentifiers as $albumIdentifier) {
            $this->insert($fileUid, $albumIdentifier);
        }
    }

    protected function insert(int $fileUid, string $albumIdentifier): void
    {
        $connection = $this->connectionPool->getConnectionForTable($this->table);
        $connection->insert(
            $this->table,
            [
                'crdate' => time(),
                'file' => $fileUid,
                'album' => $albumIdentifier,
            ],
            [
                \PDO::PARAM_INT,
                \PDO::PARAM_INT,
                \PDO::PARAM_STR,
            ]
        );
    }

    protected function deleteByFileUid(int $fileUid): void
    {
        $connection = $this->connectionPool->getConnectionForTable($this->table);
        $connection->delete(
            $this->table,
            [
                'file' => $fileUid,
            ],
            [
                \PDO::PARAM_INT,
            ]
        );
    }
}
