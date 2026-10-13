<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Tests\Database;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved.
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use In2code\In2publishCore\Component\ConfigContainer\ConfigContainer;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use In2code\In2publishCore\Utility\DatabaseUtility;
use Throwable;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileUidSequenceTest implements TestCaseInterface
{
    public function run(): TestResult
    {
        $configContainer = GeneralUtility::makeInstance(ConfigContainer::class);
        $enabled = $configContainer->get('features.foreignFileUidCheck.enable');
        $accepted = isset($GLOBALS['BE_USER'])
            && ($GLOBALS['BE_USER']->getModuleData('tx_in2publishcore_test_warning')['accepted'] ?? false) === true;

        return static::check($enabled, $accepted);
    }

    public static function check(bool $enabled, bool $accepted, ?Connection $local = null, ?Connection $foreign = null): TestResult
    {
        if (!$enabled || $accepted) {
            return new TestResult('database.file_uid_sequence.disabled', TestResult::SKIPPED);
        }

        try {
            $local ??= DatabaseUtility::buildLocalDatabaseConnection();
            $foreign ??= DatabaseUtility::buildForeignDatabaseConnection();
        } catch (Throwable) {
            return new TestResult('database.file_uid_sequence.unavailable', TestResult::WARNING);
        }

        if ($local === null || $foreign === null) {
            return new TestResult('database.file_uid_sequence.unavailable', TestResult::WARNING);
        }

        try {
            $localAutoIncrement = static::getAutoIncrement($local);
            $foreignAutoIncrement = static::getAutoIncrement($foreign);
        } catch (Throwable) {
            return new TestResult('database.file_uid_sequence.unavailable', TestResult::WARNING);
        }

        if ($localAutoIncrement === null || $foreignAutoIncrement === null) {
            return new TestResult('database.file_uid_sequence.unavailable', TestResult::WARNING);
        }
        if ($foreignAutoIncrement > $localAutoIncrement) {
            return new TestResult(
                'database.file_uid_sequence.foreign_ahead',
                TestResult::WARNING,
                ['database.file_uid_sequence.collision_risk'],
                [$foreignAutoIncrement, $localAutoIncrement],
            );
        }
        return new TestResult('database.file_uid_sequence.ok');
    }

    protected static function getAutoIncrement(Connection $connection): ?int
    {
        // MySQL 8 caches information_schema statistics; read the current sequence.
        // MariaDB ignores MySQL-specific version comments in this version range.
        $connection->executeStatement('/*!80000 SET SESSION information_schema_stats_expiry = 0 */');
        $value = $connection->fetchOne(
            'SELECT AUTO_INCREMENT FROM information_schema.TABLES '
            . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            ['sys_file'],
        );
        return $value === false || $value === null ? null : (int)$value;
    }

    public function getDependencies(): array
    {
        return [LocalDatabaseTest::class, ForeignDatabaseTest::class];
    }
}
