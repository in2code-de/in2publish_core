<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Command\Tools;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
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

use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Service\Environment\EnvironmentService;
use In2code\In2publishCore\Testing\Service\TestingService;
use In2code\In2publishCore\Testing\Tests\TestResult;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use const PHP_EOL;

class TestCommand extends Command
{
    public const EXIT_TESTS_FAILED = 240;
    public const IDENTIFIER = 'in2publish_core:tools:test';

    /** @var ContextService */
    private $contextService;

    /** @var TestingService */
    private $testingService;

    /** @var EnvironmentService */
    private $environmentService;

    public function __construct(
        ContextService $contextService,
        TestingService $testingService,
        EnvironmentService $environmentService,
        string $name = null
    ) {
        parent::__construct($name);
        $this->contextService = $contextService;
        $this->testingService = $testingService;
        $this->environmentService = $environmentService;
    }

    public function isEnabled(): bool
    {
        return $this->contextService->isLocal();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        try {
            $testingResults = $this->testingService->runAllTests();
            $success = true;

            foreach ($testingResults as $testingResult) {
                if ($testingResult->getSeverity() === TestResult::ERROR) {
                    $success = false;
                    break;
                }
            }
        } catch (Throwable $e) {
            $testingResults = [];
            $success = false;
        }

        $this->environmentService->setTestResult($success);

        if (true !== $success) {
            foreach ($testingResults as $testingResult) {
                if ($testingResult->getSeverity() === TestResult::ERROR) {
                    $errOutput->writeln($testingResult->getTranslatedLabel() . PHP_EOL);
                    $errOutput->writeln($testingResult->getTranslatedMessages() . PHP_EOL);
                }
            }
            return static::EXIT_TESTS_FAILED;
        }

        $output->writeln('All tests passed', OutputInterface::VERBOSITY_VERBOSE);
        return 0;
    }
}
