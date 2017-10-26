<?php
namespace In2code\In2publishCore\Testing\Tests\SshConnection;

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

use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SshConnectionTest
 */
class SshConnectionTest implements TestCaseInterface
{
    /**
     * @var RemoteCommandDispatcher
     */
    protected $remoteCommandDispatcher = null;

    /**
     * ForeignInstanceTest constructor.
     */
    public function __construct()
    {
        $this->remoteCommandDispatcher = GeneralUtility::makeInstance(RemoteCommandDispatcher::class);
    }

    /**
     * @return TestResult
     */
    public function run()
    {
        $request = GeneralUtility::makeInstance(RemoteCommandRequest::class);
        $request->setDispatcher('');
        $request->setOption('-v');

        try {
            $response = $this->remoteCommandDispatcher->dispatch($request);
        } catch (\Exception $exception) {
            return new TestResult(
                'ssh_connection.connection_failed',
                TestResult::ERROR,
                ['ssh_connection.connection_failure_message', $exception->getMessage()]
            );
        }

        // this is the first time a RCE is executed so we have to tes exactly here for the missing do root folder
        if (!$response->isSuccessful()) {
            if (false !== strpos($response->getErrorsString(), 'No such file or directory')) {
                return new TestResult(
                    'ssh_connection.foreign_document_root_missing',
                    TestResult::ERROR,
                    [],
                    [$request->getWorkingDirectory()]
                );
            } else {
                return new TestResult(
                    'ssh_connection.invalid_php',
                    TestResult::ERROR,
                    ['ssh_connection.php_test_error_message', $response->getErrorsString()]
                );
            }
        }

        $request = GeneralUtility::makeInstance(RemoteCommandRequest::class, 'ls');
        $request->usePhp(false);
        $request->setDispatcher('');
        $response = $this->remoteCommandDispatcher->dispatch($request);

        if ($response->isSuccessful()) {
            $documentRootFiles = GeneralUtility::trimExplode("\n", $response->getOutputString());

            $requiredNames = [
                'fileadmin',
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

        return new TestResult('ssh_connection.connection_successful');
    }

    /**
     * @return array
     */
    public function getDependencies()
    {
        return [
            SshFunctionAvailabilityTest::class,
            SshKeyFilesExistTest::class,
        ];
    }
}
