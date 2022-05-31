<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Resource\Metadata;

use Doctrine\DBAL\Platforms\SQLServerPlatform;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\RootLevelRestriction;
use TYPO3\CMS\Core\Resource\Event\AfterFileMetaDataUpdatedEvent;
use TYPO3\CMS\Core\Resource\Event\EnrichFileMetaDataEvent;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository as Typo3MetaDataRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MetadataRepository extends Typo3MetaDataRepository
{
    public function findByFileUidAndLanguageUid(int $uid, int $languageUid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $queryBuilder
            ->getRestrictions()
            ->add(GeneralUtility::makeInstance(RootLevelRestriction::class))
        ;

        $record = $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    'file',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($languageUid, Connection::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();

        if (empty($record)) {
            return [];
        }

        return $this->eventDispatcher->dispatch(new EnrichFileMetaDataEvent($uid, (int)$record['uid'], $record))->getRecord();
    }

    /**
     * Updates the metadata record in the database
     *
     * @param int $fileUid the file uid to update
     * @param array $data Data to update
     * @internal
     */
    public function updateByFileUidAndLanguageUid($fileUid, int $languageUid, array $data)
    {
        $updateRow = array_intersect_key($data, $this->getTableFields());
        if (array_key_exists('uid', $updateRow)) {
            unset($updateRow['uid']);
        }
        $row = $this->findByFileUidAndLanguageUid($fileUid, $languageUid);
        if (!empty($updateRow)) {
            $updateRow['tstamp'] = time();
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->tableName);
            $types = [];
            if ($connection->getDatabasePlatform() instanceof SQLServerPlatform) {
                // mssql needs to set proper PARAM_LOB and others to update fields
                $tableDetails = $connection->getSchemaManager()->listTableDetails($this->tableName);
                foreach ($updateRow as $columnName => $columnValue) {
                    $types[$columnName] = $tableDetails->getColumn($columnName)->getType()->getBindingType();
                }
            }
            $connection->update(
                $this->tableName,
                $updateRow,
                [
                    'uid' => (int)$row['uid']
                ],
                $types
            );

            $this->eventDispatcher->dispatch(new AfterFileMetaDataUpdatedEvent($fileUid, (int)$row['uid'], array_merge($row, $updateRow)));
        }
    }
}
