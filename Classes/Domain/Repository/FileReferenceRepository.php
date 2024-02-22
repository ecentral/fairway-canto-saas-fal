<?php

declare(strict_types = 1);

namespace Fairway\CantoSaasFal\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class FileReferenceRepository
{
    private ConnectionPool $connectionPool;
    private const TABLE_NAME = 'sys_file_reference';

    public function getFileReferenzesByFileUid(int $uid,int $deletedStatus = 0): ?array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);

        $result = $queryBuilder
            ->select('uid')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('uid_local', $uid)
            )->andWhere(
                $queryBuilder->expr()->eq('deleted', $deletedStatus)
            )->execute()->fetchAll();
        return $result;
    }
}
