<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Tests\Database;

/*
 * Copyright notice
 *
 * (c) 2022 in2code.de and the following authors:
 * Christine Zoglmeier <christine.zoglmeier@in2code.de>
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

use In2code\In2publishCore\CommonInjection\LocalDatabaseInjection;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class TableGarbageCollectorTest implements TestCaseInterface
{
    use LocalDatabaseInjection;

    public function run(): TestResult
    {
        if (!ExtensionManagementUtility::isLoaded('scheduler')) {
            return new TestResult('database.scheduler_missing', TestResult::ERROR);
        }

        $query = $this->localDatabase->createQueryBuilder();
        $query->count('*')
              ->from('tx_scheduler_task')
              ->where(
                  $query->expr()->like(
                      'serialized_task_object',
                      $query->createNamedParameter('%tx_in2publishcore_running_request%')
                  )
              );
        $statement = $query->execute();

        if (0 === $statement->fetchColumn()) {
            return new TestResult(
                'database.garbage_collector_task_missing',
                TestResult::ERROR,
                ['database.garbage_collector_task_missing.explanation']
            );
        }

        return new TestResult('database.garbage_collector_task_exists');
    }

    public function getDependencies(): array
    {
        return [
            LocalDatabaseTest::class,
        ];
    }
}
