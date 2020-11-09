<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Tests\Performance;

/*
 * Copyright notice
 *
 * (c) 2020 in2code.de and the following authors:
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

use In2code\In2publishCore\Testing\Tests\Application\ForeignDatabaseConfigTest;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use In2code\In2publishCore\Utility\DatabaseUtility;
use ReflectionProperty;

use function array_sum;
use function array_unshift;
use function count;
use function function_exists;
use function microtime;
use function sort;
use function xdebug_is_enabled;

class ForeignDbInitializationPerformanceTest implements TestCaseInterface
{
    protected const THRESHOLD = [
        TestResult::OK => 1400,
    ];

    public function run(): TestResult
    {
        $reflectionProperty = new ReflectionProperty(DatabaseUtility::class, 'foreignConnection');
        $reflectionProperty->setAccessible(true);

        $times = [];

        for ($i = 0; $i < 10; $i++) {
            $start = microtime(true);

            $reflectionProperty->setValue(null);
            DatabaseUtility::buildForeignDatabaseConnection();

            $times[] = (int)((microtime(true) - $start) * 1000000);
        }

        $median = (array_sum($times) / count($times));
        $messages = [];
        sort($times);
        $messages[] = 'Fastest: ' . $times[0] . ' msec';
        $messages[] = 'Slowest: ' . $times[9] . ' msec';

        $severity = TestResult::WARNING;
        if ($median < self::THRESHOLD[TestResult::OK]) {
            $severity = TestResult::OK;
        }
        if ($severity !== TestResult::OK) {
            array_unshift($messages, 'performance.db_init.slow_help');
        }
        if (function_exists('xdebug_is_enabled') && xdebug_is_enabled()) {
            $severity = TestResult::WARNING;
            array_unshift($messages, 'performance.db_init.xdebug_enabled');
        }

        return new TestResult(
            'performance.db_init.init_time',
            $severity,
            $messages,
            [(string)$median]
        );
    }

    public function getDependencies(): array
    {
        return [
            ForeignDatabaseConfigTest::class,
        ];
    }
}
