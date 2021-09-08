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

use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;

use function function_exists;

class SshFunctionAvailabilityTest implements TestCaseInterface
{
    /**
     * @return TestResult
     */
    public function run(): TestResult
    {
        $sshFunctions = [
            'ssh2_connect',
            'ssh2_sftp_mkdir',
            'ssh2_exec',
            'ssh2_fingerprint',
            'ssh2_auth_pubkey_file',
            'ssh2_sftp',
        ];

        foreach ($sshFunctions as $index => $sshFunction) {
            if (function_exists($sshFunction)) {
                unset($sshFunctions[$index]);
            }
        }

        if (!empty($sshFunctions)) {
            return new TestResult('ssh_connection.functions_missing', TestResult::ERROR);
        }

        if (!function_exists('ssh2_sftp_chmod')) {
            return new TestResult(
                'ssh_connection.chmod_missing',
                TestResult::WARNING,
                ['ssh_connection.chmod_description']
            );
        }

        return new TestResult('ssh_connection.full_availability');
    }

    /**
     * @return array
     */
    public function getDependencies(): array
    {
        return [];
    }
}
