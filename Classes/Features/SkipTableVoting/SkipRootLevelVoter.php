<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\SkipTableVoting;

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

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\CommonRepository as CR;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_key_exists;
use function in_array;

class SkipRootLevelVoter implements SingletonInterface
{
    public function shouldSkipSearchingForRelatedRecordsByProperty(array $votes, CR $repository, array $args): array
    {
        /** @var RecordInterface $record */
        $record = $args['record'];
        $pid = $record->getPageIdentifier();

        $config = $args['columnConfiguration'];
        if (empty($config['type']) || !in_array($config['type'], ['select', 'group', 'inline'])) {
            return [$votes, $repository, $args];
        }

        if ($this->shouldSkipTableByConfig($config, $pid)) {
            $votes['yes']++;
        }

        return [$votes, $repository, $args];
    }

    public function shouldSkipSearchingForRelatedRecordByTable(array $votes, CR $repository, array $arguments): array
    {
        /** @var string $table */
        $table = $arguments['tableName'];
        /** @var RecordInterface $record */
        $record = $arguments['record'];
        /** @var int $pid */
        $pid = $record->getIdentifier();

        if (!$this->isTableAllowedOnPid($table, $pid)) {
            $votes['yes']++;
        }
        return [$votes, $repository, $arguments];
    }

    protected function shouldSkipTableByConfig(array $config, int $pid): bool
    {
        switch ($config['type']) {
            case 'group':
                if (
                    !array_key_exists('internal_type', $config)
                    || 'db' !== $config['internal_type']
                    || !array_key_exists('allowed', $config)
                    || empty($config['allowed'])
                ) {
                    return false;
                }
                $tables = GeneralUtility::trimExplode(',', $config['allowed']);
                foreach ($tables as $table) {
                    if ('*' === $table) {
                        return false;
                    }
                    if ($this->isTableAllowedOnPid($table, $pid)) {
                        return false;
                    }
                }
                return true;
            case 'select':
            case 'inline':
                if (array_key_exists('foreign_table', $config)) {
                    return !$this->isTableAllowedOnPid($config['foreign_table'], $pid);
                }
        }
        return false;
    }

    protected function isTableAllowedOnPid(string $table, int $pid): bool
    {
        /* rootLevel:
         * -1 = Can exist in both page tree and root
         *  0 = Can only exist in the page tree -> PID must be > 0
         *  1 = Can only exist in the root -> PID must be 0
         */
        $rootLevel = $this->getRootLevel($table);
        if (-1 === $rootLevel) {
            return true;
        }
        if (0 === $rootLevel && 0 !== $pid) {
            return true;
        }
        if (1 === $rootLevel && 0 === $pid) {
            return true;
        }
        return false;
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function getRootLevel(string $table): int
    {
        if ('pages' === $table) {
            return -1;
        }
        return (int)($GLOBALS['TCA'][$table]['ctrl']['rootLevel'] ?? 0);
    }
}
