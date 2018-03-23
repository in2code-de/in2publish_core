<?php
namespace In2code\In2publishCore\Command;

/***************************************************************
 * Copyright notice
 *
 * (c) 2017 in2code.de and the following authors:
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

use In2code\In2publishCore\Service\Environment\EnvironmentService;
use In2code\In2publishCore\Testing\Service\TestingService;
use In2code\In2publishCore\Testing\Tests\TestResult;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ToolsCommandController
 */
class ToolsCommandController extends AbstractCommandController
{
    const TESTS_FAILED = 240;

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testCommand()
    {
        $testingService = GeneralUtility::makeInstance(TestingService::class);
        try {
            $testingResults = $testingService->runAllTests();
            $success = true;

            foreach ($testingResults as $testingResult) {
                if ($testingResult->getSeverity() === TestResult::ERROR) {
                    $success = false;
                    break;
                }
            }
        } catch (\Exception $e) {
            $testingResults = [];
            $success = false;
        }

        $environmentService = GeneralUtility::makeInstance(EnvironmentService::class);
        $environmentService->setTestResult($success);

        if (true !== $success) {
            foreach ($testingResults as $testingResult) {
                if ($testingResult->getSeverity() === TestResult::ERROR) {
                    $this->response->appendContent($testingResult->getTranslatedLabel() . PHP_EOL);
                    $this->response->appendContent($testingResult->getTranslatedMessages() . PHP_EOL);
                }
            }
            $this->response->setExitCode(static::TESTS_FAILED);
        }
    }
}
