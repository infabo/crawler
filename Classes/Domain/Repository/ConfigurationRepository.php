<?php

declare(strict_types=1);

namespace AOE\Crawler\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 AOE GmbH <dev@aoe.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ConfigurationRepository
 */
class ConfigurationRepository extends AbstractRepository
{
    /**
     * @var string
     */
    protected $tableName = 'tx_crawler_configuration';

    /**
     * @return array
     */
    public function getCrawlerConfigurationRecords(): array
    {
        $records = [];
        $queryBuilder = $this->createQueryBuilder();
        $statement = $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->execute();

        while ($row = $statement->fetch()) {
            $records[] = $row;
        }

        return $records;
    }

    /**
     * Traverses up the rootline of a page and fetches all crawler records.
     *
     * @param int $pageId
     * @return array
     */
    public function getCrawlerConfigurationRecordsFromRootLine(int $pageId): array
    {
        $pageIdsInRootLine = [];
        $rootLine = BackendUtility::BEgetRootLine($pageId);

        foreach ($rootLine as $pageInRootLine) {
            $pageIdsInRootLine[] = (int)$pageInRootLine['uid'];
        }

        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder
            ->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        $configurationRecordsForCurrentPage = $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->in('pid', $queryBuilder->createNamedParameter($pageIdsInRootLine, Connection::PARAM_INT_ARRAY))
            )
            ->execute()
            ->fetchAll();
        return is_array($configurationRecordsForCurrentPage) ? $configurationRecordsForCurrentPage : [];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
    }
}
