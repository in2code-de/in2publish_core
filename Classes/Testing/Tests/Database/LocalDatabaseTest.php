<?php
namespace In2code\In2publishCore\Testing\Tests\Database;

/***************************************************************
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
 ***************************************************************/

use In2code\In2publishCore\Testing\Data\RequiredTablesDataProvider;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class LocalDatabaseAccessTest
 */
class LocalDatabaseTest implements TestCaseInterface
{
    /**
     * @return TestResult
     */
    public function run()
    {
        $localDatabase = DatabaseUtility::buildLocalDatabaseConnection();

        if (!($localDatabase instanceof DatabaseConnection)) {
            return new TestResult('database.local_inaccessible', TestResult::ERROR);
        }

        if (!$localDatabase->isConnected()) {
            return new TestResult('database.local_offline', TestResult::ERROR);
        }

        $expectedTables = GeneralUtility::makeInstance(
            RequiredTablesDataProvider::class
        )->getRequiredTables();
        $actualTables = array_keys($localDatabase->admin_get_tables());

        $missingTables = [];
        foreach ($expectedTables as $expectedTable) {
            if (!in_array($expectedTable, $actualTables)) {
                $missingTables[] = $expectedTable;
            }
        }

        if (!empty($missingTables)) {
            return new TestResult(
                'database.local_tables_missing',
                TestResult::ERROR,
                array_merge(['database.missing_important_tables'], $missingTables)
            );
        }

        return new TestResult('database.local_accessible_and_tables_present');
    }

    /**
     * @return array
     */
    public function getDependencies()
    {
        return [];
    }
}
