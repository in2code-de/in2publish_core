<?php
namespace In2code\In2publishCore\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 in2code.de
 *  Alex Kellner <alexander.kellner@in2code.de>,
 *  Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class EnvironmentUtility
 */
class EnvironmentUtility
{
    const STATE_TESTS_FAILING = 'tests_failing';
    const STATE_TESTS_NEVER_RAN = 'tests_never_ran';
    const STATE_PACKAGES_CHANGED = 'environment_changed';
    const STATE_CONFIGURATION_CHANGED = 'configuration_changed';

    /**
     * @return array
     */
    public static function getTestStatus()
    {
        $statusArray = array();
        /** @var Registry $registry */
        $registry = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
        $testResults = $registry->get('tx_in2publish', 'test_result', false);
        if ($testResults === false) {
            return array(self::STATE_TESTS_NEVER_RAN);
        }
        if ($testResults['packages_hash'] !== self::getPackagesHash()) {
            $statusArray[] = self::STATE_PACKAGES_CHANGED;
        }
        if ($testResults['configuration_hash'] !== self::getConfigurationHash()) {
            $statusArray[] = self::STATE_CONFIGURATION_CHANGED;
        }
        if ($testResults['success'] !== true) {
            $statusArray[] = self::STATE_TESTS_FAILING;
        }
        return $statusArray;
    }

    /**
     * @return string
     */
    public static function getPackagesHash()
    {
        $packageManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Package\\PackageManager');
        return sha1(json_encode($packageManager->getActivePackages()));
    }

    /**
     * @return string
     */
    public static function getConfigurationHash()
    {
        return ConfigurationUtility::getFileConfigurationHash();
    }

    /**
     * Return "http://" or "https://" - depending on current URI
     *
     * @return string
     */
    public static function getCurrentProtocol()
    {
        $protocol = 'http://';
        if (GeneralUtility::getIndpEnv('TYPO3_SSL')) {
            $protocol = 'https://';
        }
        return $protocol;
    }
}
