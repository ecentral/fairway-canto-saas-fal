<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Resource\Metadata;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\RootLevelRestriction;
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
}
