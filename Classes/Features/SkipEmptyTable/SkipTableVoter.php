<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\SkipEmptyTable;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use In2code\In2publishCore\Event\VoteIfFindingByPropertyShouldBeSkipped;
use In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped;
use In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsByTableShouldBeSkipped;
use In2code\In2publishCore\Features\SkipEmptyTable\Service\TableInfoService as TIS;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_key_exists;
use function in_array;

class SkipTableVoter
{
    protected TIS $tis;

    public function __construct(TIS $tableInfoService)
    {
        $this->tis = $tableInfoService;
    }

    /**
     * Skip searching for records in tables based by PID.
     *  1. Skip tables which are empty
     *  2. Skip tables which do not contain the searched PID
     */
    public function shouldSkipSearchingForRelatedRecordByTable(
        VoteIfSearchingForRelatedRecordsByTableShouldBeSkipped $event
    ): void {
        $table = $event->getTableName();

        if ($this->tis->isEmptyTable($table)) {
            $event->voteYes();
        } else {
            $pid = $event->getRecord()->getIdentifier();
            if (!$this->tis->isPidInTable($table, $pid)) {
                $event->voteYes();
            }
        }
    }

    /**
     * Skip searching for related records by TCA, if the table to search in is empty
     */
    public function shouldSkipSearchingForRelatedRecordsByProperty(
        VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped $event
    ): void {
        $config = $event->getColumnConfiguration();
        if (empty($config['type']) || !in_array($config['type'], ['select', 'group', 'inline'])) {
            return;
        }

        if (array_key_exists('MM', $config) && $this->tis->isEmptyTable($config['MM'])) {
            $event->voteYes();
        } elseif (array_key_exists('foreign_table', $config) && $this->tis->isEmptyTable($config['foreign_table'])) {
            $event->voteYes();
        } elseif ($this->isGroupDbWhereAllAllowedTablesAreEmpty($config)) {
            $event->voteYes();
        }
    }

    protected function isGroupDbWhereAllAllowedTablesAreEmpty(array $config): bool
    {
        if (
            'group' === $config['type']
            && 'db' === ($config['internal_type'] ?? 'db')
            && array_key_exists('allowed', $config)
        ) {
            $tables = GeneralUtility::trimExplode(',', $config['allowed']);
            foreach ($tables as $table) {
                if ('*' === $table) {
                    return false;
                }
                if (!$this->tis->isEmptyTable($table)) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Skip searching for related records by TCA, if the table to search in is empty
     */
    public function shouldSkipFindByProperty(VoteIfFindingByPropertyShouldBeSkipped $event): void
    {
        if ($this->tis->isEmptyTable($event->getTableName())) {
            $event->voteYes();
        }
    }
}
