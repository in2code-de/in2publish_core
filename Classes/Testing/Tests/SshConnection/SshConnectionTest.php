<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Tests\SshConnection;

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

use In2code\In2publishCore\Component\ConfigContainer\ConfigContainer;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use Throwable;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_diff;
use function preg_match;

class SshConnectionTest implements TestCaseInterface
{
    protected RemoteCommandDispatcher $rceDispatcher;
    protected ConfigContainer $configContainer;

    public function __construct(RemoteCommandDispatcher $remoteCommandDispatcher, ConfigContainer $configContainer)
    {
        $this->rceDispatcher = $remoteCommandDispatcher;
        $this->configContainer = $configContainer;
    }

    public function run(): TestResult
    {
        $request = new RemoteCommandRequest();
        $request->setDispatcher('');
        $request->usePhp(false);
        $request->setCommand('echo ""');
        $request->setEnvironmentVariables([]);

        try {
            $response = $this->rceDispatcher->dispatch($request);
        } catch (Throwable $exception) {
            return new TestResult(
                'ssh_connection.connection_failed',
                TestResult::ERROR,
                ['ssh_connection.connection_failure_message', $exception->getMessage()]
            );
        }

        // This is the first time a RCE is executed, so we have to test here for the missing document root folder
        if (!$response->isSuccessful()) {
            return new TestResult(
                'ssh_connection.foreign_document_root_missing',
                TestResult::ERROR,
                [$response->getErrorsString()],
                [$request->getWorkingDirectory()]
            );
        }

        // Test the php binary
        $request = new RemoteCommandRequest();
        $request->setDispatcher('');
        $request->setCommand('-v');
        $response = $this->rceDispatcher->dispatch($request);

        if (!$response->isSuccessful()) {
            return new TestResult(
                'ssh_connection.invalid_php',
                TestResult::ERROR,
                ['ssh_connection.php_test_error_message', $response->getErrorsString()]
            );
        }

        // Probe for required TYPO3 indicators
        $request = new RemoteCommandRequest('ls');
        $request->usePhp(false);
        $request->setDispatcher('');
        $response = $this->rceDispatcher->dispatch($request);

        if ($response->isSuccessful()) {
            $documentRootFiles = GeneralUtility::trimExplode("\n", $response->getOutputString());

            $requiredNames = [
                'typo3',
                'index.php',
                'typo3conf',
            ];

            if (!empty(array_diff($requiredNames, $documentRootFiles))) {
                return new TestResult('ssh_connection.foreign_document_root_wrong', TestResult::ERROR);
            }
        } else {
            return new TestResult(
                'ssh_connection.foreign_document_validation_error',
                TestResult::ERROR,
                [
                    'ssh_connection.foreign_document_validation_error_reason',
                    $response->getOutputString(),
                    $response->getErrorsString(),
                ]
            );
        }

        // Actually call the foreign cli dispatcher
        $request = new RemoteCommandRequest('help');
        $response = $this->rceDispatcher->dispatch($request);
        if (!$response->isSuccessful()) {
            if (1 === preg_match('~The given context "(.*)" was not valid~', $response->getOutputString(), $match)) {
                return new TestResult(
                    'ssh_connection.wrong_context',
                    TestResult::ERROR,
                    $response->getErrors(),
                    [$match[1]]
                );
            }

            return new TestResult(
                'ssh_connection.dispatcher_unknown_error',
                TestResult::ERROR,
                $response->getErrors()
            );
        }

        return new TestResult('ssh_connection.connection_successful');
    }

    public function getDependencies(): array
    {
        return [
            SshFunctionAvailabilityTest::class,
        ];
    }
}
