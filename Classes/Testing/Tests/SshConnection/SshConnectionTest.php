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

use In2code\In2publishCore\Security\SshConnection;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SshConnectionTest
 */
class SshConnectionTest implements TestCaseInterface
{
    /**
     * @return TestResult
     */
    public function run()
    {
        $sshConnection = SshConnection::makeInstance();
        try {
            $phpVersion = $sshConnection->testConnection();
            if ($phpVersion['code'] > 0) {
                return new TestResult(
                    'ssh_connection.invalid_php',
                    TestResult::ERROR,
                    array('ssh_connection.php_test_error_message', $phpVersion['stdErr'])
                );
            }

            $result = $sshConnection->validateForeignDocumentRoot();

            if (0 === (int)$result['code']) {
                $documentRootFiles = GeneralUtility::trimExplode(PHP_EOL, $result['stdOut']);

                $requiredNames = array(
                    'fileadmin',
                    'typo3',
                    'index.php',
                    'typo3conf',
                );

                if (!empty(array_diff($requiredNames, $documentRootFiles))) {
                    return new TestResult('ssh_connection.foreign_document_root_wrong', TestResult::ERROR);
                }
            } else {
                return new TestResult(
                    'ssh_connection.foreign_document_validation_error',
                    TestResult::ERROR,
                    array(
                        'ssh_connection.foreign_document_validation_error_reason',
                        $result['stdOut'],
                        $result['stdErr'],
                    )
                );
            }
        } catch (\Exception $exception) {
            return new TestResult(
                'ssh_connection.connection_failed',
                TestResult::ERROR,
                array('ssh_connection.connection_failure_message', $exception->getMessage())
            );
        }

        return new TestResult('ssh_connection.connection_successful');
    }

    /**
     * @return array
     */
    public function getDependencies()
    {
        return array(
            'In2code\\In2publishCore\\Testing\\Tests\\SshConnection\\SshFunctionAvailabilityTest',
        );
    }
}
