<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Service;

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

use In2code\In2publishCore\Testing\Tests\TestResult;

use function array_intersect;
use function array_keys;
use function array_merge;

class TestingService
{
    protected TestCaseService $testCaseService;

    /**
     * @codeCoverageIgnore
     * @noinspection PhpUnused
     */
    public function __construct(TestCaseService $testCaseService)
    {
        $this->testCaseService = $testCaseService;
    }

    /**
     * @return TestResult[]
     */
    public function runAllTests(): array
    {
        $failedTests = [];
        $skippedTests = [];
        $successfulTests = [];
        $warningTests = [];

        foreach ($this->testCaseService->getTests() as $testClass => $testCase) {
            if ($this->hasDependencyFailed($testCase->getDependencies(), $failedTests)) {
                $skippedTests[$testClass] = new TestResult(
                    'test_skipped',
                    TestResult::SKIPPED,
                    array_merge(
                        ['dependency_failed'],
                        array_intersect($testCase->getDependencies(), array_keys($failedTests)),
                    ),
                    [$testClass],
                );
            } elseif ($this->hasDependencyFailed($testCase->getDependencies(), $skippedTests)) {
                $skippedTests[$testClass] = new TestResult(
                    'test_skipped',
                    TestResult::SKIPPED,
                    array_merge(
                        ['dependency_skipped'],
                        array_intersect($testCase->getDependencies(), array_keys($skippedTests)),
                    ),
                    [$testClass],
                );
            } else {
                $result = $testCase->run();
                $severity = $result->getSeverity();
                if ($severity === TestResult::OK) {
                    $successfulTests[$testClass] = $result;
                } elseif ($severity === TestResult::WARNING) {
                    $warningTests[$testClass] = $result;
                } else {
                    $failedTests[$testClass] = $result;
                }
            }
        }

        return array_merge($failedTests, $warningTests, $skippedTests, $successfulTests);
    }

    protected function hasDependencyFailed(array $dependencies, array $failedTests): bool
    {
        foreach ($dependencies as $dependency) {
            if (isset($failedTests[$dependency])) {
                return true;
            }
        }
        return false;
    }
}
