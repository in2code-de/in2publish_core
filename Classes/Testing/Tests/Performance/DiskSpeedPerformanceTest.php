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

use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_unshift;
use function fclose;
use function fgets;
use function fopen;
use function function_exists;
use function fwrite;
use function microtime;
use function random_bytes;
use function register_shutdown_function;
use function unlink;
use function xdebug_is_enabled;

class DiskSpeedPerformanceTest implements TestCaseInterface
{
    protected const THRESHOLD = [
        'read' => [
            TestResult::OK => 0.05,
        ],
        'write' => [
            TestResult::OK => 0.1,
        ],
    ];

    public function run(): TestResult
    {
        $canaryFile = GeneralUtility::tempnam('tx_contentpublisher_test_');
        register_shutdown_function(
            function () use ($canaryFile) {
                unlink($canaryFile);
            }
        );
        $targetFile = GeneralUtility::tempnam('tx_contentpublisher_test_');
        register_shutdown_function(
            function () use ($targetFile) {
                unlink($targetFile);
            }
        );

        $canaryTarget = fopen($canaryFile, 'w');
        for ($i = 0; $i < 10; $i++) {
            $bytes = random_bytes(1024 * 1024);
            fwrite($canaryTarget, $bytes);
        }
        fclose($canaryTarget);

        // Read speed
        $canaryTarget = fopen($canaryFile, 'r');
        $start = microtime(true);
        /**
         * @noinspection LoopWhichDoesNotLoopInspection
         * @noinspection PhpStatementHasEmptyBodyInspection
         * @noinspection MissingOrEmptyGroupStatementInspection
         */
        while (fgets($canaryTarget, 1024)) {
            // Do nothing with the read content
        }
        $readTime = microtime(true) - $start;
        fclose($canaryTarget);

        // Write speed
        $canarySource = fopen($canaryFile, 'r');
        $canaryTarget = fopen($targetFile, 'w');
        $start = microtime(true);
        while ($bytes = fgets($canarySource, 1024)) {
            fwrite($canaryTarget, $bytes);
        }
        $readAndWriteTime = microtime(true) - $start;
        $writeTime = $readAndWriteTime - $readTime;

        $messages[] = 'Read: ' . $readTime . ' msec';
        $messages[] = 'Write: ' . $writeTime . ' msec';

        $severity = TestResult::WARNING;
        if (
            $readTime < self::THRESHOLD['read'][TestResult::OK]
            && $writeTime < self::THRESHOLD['write'][TestResult::OK]
        ) {
            $severity = TestResult::OK;
        }
        if ($severity !== TestResult::OK) {
            array_unshift($messages, 'performance.fs_io.slow_help');
        }
        if (function_exists('xdebug_is_enabled') && xdebug_is_enabled()) {
            $severity = TestResult::WARNING;
            array_unshift($messages, 'performance.fs_io.xdebug_enabled');
        }

        return new TestResult(
            'performance.fs_io.rw_time',
            $severity,
            $messages,
            [(string)$readAndWriteTime]
        );
    }

    public function getDependencies(): array
    {
        return [];
    }
}
