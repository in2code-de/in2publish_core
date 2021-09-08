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

use In2code\In2publishCore\In2publishCoreException;
use In2code\In2publishCore\Testing\Tests\TestResult;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_merge;
use function get_class;

class TestingService
{
    /**
     * @var TestCaseService
     */
    protected $testCaseService = null;

    /**
     * TestingService constructor.
     */
    public function __construct()
    {
        $this->testCaseService = GeneralUtility::makeInstance(TestCaseService::class);
    }

    /**
     * @return TestResult[]
     *
     * @throws In2publishCoreException
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
                        array_intersect($testCase->getDependencies(), array_keys($failedTests))
                    ),
                    [$testClass]
                );
            } elseif ($this->hasDependencyFailed($testCase->getDependencies(), $skippedTests)) {
                $skippedTests[$testClass] = new TestResult(
                    'test_skipped',
                    TestResult::SKIPPED,
                    array_merge(
                        ['dependency_skipped'],
                        array_intersect($testCase->getDependencies(), array_keys($skippedTests))
                    ),
                    [$testClass]
                );
            } else {
                $result = $testCase->run();
                if (!($result instanceof TestResult)) {
                    throw new In2publishCoreException(
                        'The test ' . get_class($testCase) . ' did not return a valid TestResult',
                        1498495165
                    );
                }
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

    /**
     * @param array $dependencies
     * @param array $failedTests
     *
     * @return bool
     */
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
