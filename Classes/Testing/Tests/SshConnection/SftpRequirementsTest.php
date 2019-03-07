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
use function ini_get;

/**
 * Class SftpRequirementsTest
 */
class SftpRequirementsTest implements TestCaseInterface
{
    /**
     * @return TestResult
     */
    public function run(): TestResult
    {
        if (false === (bool)ini_get('allow_url_fopen')) {
            return new TestResult(
                'sftp_requirements.allow_url_fopen_disabled',
                TestResult::ERROR
            );
        }

        return new TestResult(
            'sftp_requirements.all_known_dependencies_okay',
            TestResult::OK
        );
    }

    /**
     * @return array
     */
    public function getDependencies(): array
    {
        return [
            SshFunctionAvailabilityTest::class,
        ];
    }
}
