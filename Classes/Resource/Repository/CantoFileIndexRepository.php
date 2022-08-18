<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Resource\Repository;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CantoFileIndexRepository extends FileIndexRepository
{
    protected array $fieldsWithTableNamePrefix;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct($eventDispatcher);
        $this->fieldsWithTableNamePrefix = array_map(
            function ($fieldName) {
                return sprintf('%s.%s', $this->table, $fieldName);
            },
            $this->fields
        );
    }

    public function findByFolder(Folder $folder): ?array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->table);

        $result = $queryBuilder
            ->select(...$this->fieldsWithTableNamePrefix)
            ->from($this->table)
            ->leftJoin(
                $this->table,
                'sys_file_canto_album',
                'sys_file_canto_album',
                $queryBuilder->expr()->eq(
                    $this->table . '.uid',
                    $queryBuilder->quoteIdentifier('sys_file_canto_album.file')
                )
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'sys_file_canto_album.album',
                    $queryBuilder->createNamedParameter($folder->getIdentifier())
                ),
                $queryBuilder->expr()->eq(
                    $this->table . '.storage',
                    $queryBuilder->createNamedParameter($folder->getStorage()->getUid(), \PDO::PARAM_INT)
                )
            )
            ->groupBy($this->table . '.uid')
            ->execute();

        $resultRows = [];
        if (method_exists($result, 'fetchAssociative')) {
            while ($row = $result->fetchAssociative()) {
                $resultRows[$row['identifier']] = $row;
            }
        } elseif (method_exists($result, 'fetchAll')) {
            // Backward-Compatibility with doctrine/dbal < 2.11
            while ($row = $result->fetchAll(\Doctrine\DBAL\FetchMode::ASSOCIATIVE)) {
                $resultRows[$row['identifier']] = $row;
            }
        }

        return $resultRows;
    }

    /**
     * @param Folder[] $folders
     * @param bool $includeMissing
     * @param string $fileName
     */
    public function findByFolders(array $folders, $includeMissing = true, $fileName = null): array
    {
        $storageUids = [];
        $folderIdentifiers = [];

        foreach ($folders as $folder) {
            if (!$folder instanceof Folder) {
                continue;
            }

            $storageUids[] = (int)$folder->getStorage()->getUid();
            $folderIdentifiers[] = $folder->getIdentifier();
        }

        $storageUids = array_unique($storageUids);
        $folderIdentifiers = array_unique($folderIdentifiers);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->table);

        $queryBuilder
            ->select(...$this->fieldsWithTableNamePrefix)
            ->from($this->table)
            ->leftJoin(
                $this->table,
                'sys_file_canto_album',
                'sys_file_canto_album',
                $queryBuilder->expr()->eq(
                    $this->table . '.uid',
                    $queryBuilder->quoteIdentifier('sys_file_canto_album.file')
                )
            )
            ->where(
                $queryBuilder->expr()->in(
                    'sys_file_canto_album.album',
                    $queryBuilder->createNamedParameter($folderIdentifiers, Connection::PARAM_STR_ARRAY)
                ),
                $queryBuilder->expr()->in(
                    'storage',
                    $queryBuilder->createNamedParameter($storageUids, Connection::PARAM_INT_ARRAY)
                )
            )
            ->groupBy($this->table . '.uid');

        if (isset($fileName)) {
            $nameParts = str_getcsv($fileName, ' ');
            foreach ($nameParts as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $queryBuilder->andWhere(
                        $queryBuilder->expr()->like(
                            'name',
                            $queryBuilder->createNamedParameter(
                                '%' . $queryBuilder->escapeLikeWildcards($part) . '%',
                                \PDO::PARAM_STR
                            )
                        )
                    );
                }
            }
        }

        if (!$includeMissing) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    'missing',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            );
        }

        $result = $queryBuilder->execute();

        $fileRecords = [];
        if (method_exists($result, 'fetchAssociative')) {
            while ($fileRecord = $result->fetchAssociative()) {
                $fileRecords[$fileRecord['identifier']] = $fileRecord;
            }
        } elseif (method_exists($result, 'fetch')) {
            while ($fileRecord = $result->fetch(\Doctrine\DBAL\FetchMode::ASSOCIATIVE)) {
                $fileRecords[$fileRecord['identifier']] = $fileRecord;
            }
        }

        return $fileRecords;
    }
}
