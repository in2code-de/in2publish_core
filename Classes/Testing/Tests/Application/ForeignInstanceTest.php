<?php
namespace In2code\In2publishCore\Testing\Tests\Application;

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
use In2code\In2publishCore\Utility\ConfigurationUtility;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class ForeignInstanceTest
 */
class ForeignInstanceTest implements TestCaseInterface
{
    /**
     * @return TestResult
     */
    public function run()
    {
        $sshConnection = SshConnection::makeInstance();
        $foreignDatabase = DatabaseUtility::buildForeignDatabaseConnection();

        if ($foreignDatabase->exec_SELECTcountRows('*', 'sys_domain', 'hidden=0') === 0) {
            return new TestResult('application.foreign_sys_domain_missing', TestResult::ERROR);
        }
        $dispatcherResult = $sshConnection->callForeignCliDispatcherCallable();

        if (false !== strpos($dispatcherResult['stdOut'], '_cli_lowlevel')) {
            return new TestResult(
                'application.foreign_cli_lowlevel_user_missing',
                TestResult::ERROR,
                array('application.foreign_cli_lowlevel_user_missing_message', $dispatcherResult['stdOut'])
            );
        }

        if (0 !== (int)$dispatcherResult['code']) {
            return new TestResult(
                'application.foreign_cli_dispatcher_not_callable',
                TestResult::ERROR,
                array(
                    'application.foreign_cli_dispatcher_error_message',
                    $dispatcherResult['stdOut'],
                    $dispatcherResult['stdErr'],
                )
            );
        }

        $localVersion = ExtensionManagementUtility::getExtensionVersion('in2publish_core');
        $foreignVersion = $sshConnection->getForeignIn2publishVersion();
        $foreignVersion = trim(substr($foreignVersion, strpos($foreignVersion, ':') + 1));

        if ($localVersion !== $foreignVersion) {
            return new TestResult(
                'application.foreign_version_differs',
                TestResult::ERROR,
                array('application.local_version', $localVersion, 'application.foreign_version', $foreignVersion)
            );
        }

        $foreignConfigState = $sshConnection->getForeignConfigurationState();
        $foreignConfigState = trim(substr($foreignConfigState, strpos($foreignConfigState, ':') + 1));
        if ($foreignConfigState !== ConfigurationUtility::STATE_LOADED) {
            return new TestResult(
                'application.foreign_configuration_not_loaded',
                TestResult::ERROR,
                array(
                    'application.foreign_configuration_loading_state',
                    LocalizationUtility::translate($foreignConfigState, 'in2publish_core'),
                )
            );
        }

        $foreignVersion = $sshConnection->getForeignTypo3Version();
        if ($foreignVersion !== TYPO3_version) {
            return new TestResult(
                'application.different_t3_versions',
                TestResult::ERROR,
                array(
                    'application.local_t3_versions',
                    TYPO3_version,
                    'application.foreign_t3_versions',
                    $foreignVersion,
                )
            );
        }

        if ($sshConnection->getForeignGlobalConfiguration() === 'Utf8Filesystem: 1') {
            return new TestResult(
                'application.foreign_utf8_fs',
                TestResult::ERROR,
                array('application.utf8_fs_errors')
            );
        }

        return new TestResult('application.foreign_system_validated');
    }

    /**
     * @return array
     */
    public function getDependencies()
    {
        return array(
            'In2code\\In2publishCore\\Testing\\Tests\\SshConnection\\SshConnectionTest',
            'In2code\\In2publishCore\\Testing\\Tests\\Database\\ForeignDatabaseTest',
        );
    }
}
