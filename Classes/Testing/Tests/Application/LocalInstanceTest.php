<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Tests\Application;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
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

use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Testing\Tests\Database\LocalDatabaseTest;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_flip;
use function array_merge;

class LocalInstanceTest implements TestCaseInterface
{
    /**
     * @return TestResult
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function run(): TestResult
    {
        $localDatabase = DatabaseUtility::buildLocalDatabaseConnection();

        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem'])) {
            return new TestResult('application.local_utf8_fs', TestResult::ERROR, ['application.utf8_fs_errors']);
        }

        $excludedTables = GeneralUtility::makeInstance(ConfigContainer::class)->get('excludeRelatedTables');
        $localTables = array_flip($localDatabase->getSchemaManager()->listTableNames());

        $missingTables = [];

        foreach ($excludedTables as $table) {
            if (!isset($localTables[$table])) {
                $missingTables[] = $table;
            }
        }

        if (!empty($missingTables)) {
            return new TestResult(
                'application.superfluous_excluded_tables_detected',
                TestResult::WARNING,
                array_merge(['application.superfluous_excluded_tables'], $missingTables)
            );
        }

        return new TestResult('application.local_instance_validated');
    }

    /**
     * @return array
     */
    public function getDependencies(): array
    {
        return [
            LocalDatabaseTest::class,
        ];
    }
}
