<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Tests\Database;

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

use In2code\In2publishCore\Factory\ConnectionFactory;
use In2code\In2publishCore\Factory\Exception\ConnectionUnavailableException;
use In2code\In2publishCore\Testing\Data\RequiredTablesDataProvider;
use In2code\In2publishCore\Testing\Tests\Adapter\RemoteAdapterTest;
use In2code\In2publishCore\Testing\Tests\Configuration\ConfigurationFormatTest;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;

use function array_merge;
use function in_array;

class ForeignDatabaseTest implements TestCaseInterface
{
    protected RequiredTablesDataProvider $requiredTablesDataProvider;
    protected ConnectionFactory $connectionFactory;

    public function __construct(RequiredTablesDataProvider $requiredTablesDataProvider)
    {
        $this->requiredTablesDataProvider = $requiredTablesDataProvider;
    }

    public function injectConnectionFactory(ConnectionFactory $connectionFactory): void
    {
        $this->connectionFactory = $connectionFactory;
    }

    public function run(): TestResult
    {
        try {
            $foreignDatabase = $this->connectionFactory->createForeignConnection();
        } catch (ConnectionUnavailableException $e) {
            return new TestResult('database.foreign_inaccessible', TestResult::ERROR);
        }

        if (!$foreignDatabase->isConnected()) {
            return new TestResult('database.foreign_offline', TestResult::ERROR);
        }

        $expectedTables = $this->requiredTablesDataProvider->getRequiredTables();
        $actualTables = $foreignDatabase->getSchemaManager()->listTableNames();

        $missingTables = [];
        foreach ($expectedTables as $expectedTable) {
            if (!in_array($expectedTable, $actualTables, true)) {
                $missingTables[] = $expectedTable;
            }
        }

        if (!empty($missingTables)) {
            return new TestResult(
                'database.foreign_tables_missing',
                TestResult::ERROR,
                array_merge(['database.missing_important_tables'], $missingTables)
            );
        }

        return new TestResult('database.foreign_accessible_and_tables_present');
    }

    public function getDependencies(): array
    {
        return [
            ConfigurationFormatTest::class,
            RemoteAdapterTest::class,
        ];
    }
}
