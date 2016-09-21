<?php
namespace In2code\In2publishCore\Testing\Tests\Fal;

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

use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use In2code\In2publishCore\Utility\DatabaseUtility;

/**
 * Class ResourceStorageTest
 */
class ResourceStorageTest implements TestCaseInterface
{
    /**
     * @return TestResult Returns a TestResult object holding all information about the failure or success
     */
    public function run()
    {
        $foreignDatabase = DatabaseUtility::buildForeignDatabaseConnection();
        $localDatabase = DatabaseUtility::buildLocalDatabaseConnection();

        $foreignStorages = $foreignDatabase->exec_SELECTgetRows('*', 'sys_file_storage', '1=1', '', '', '', 'uid');
        $localStorages = $localDatabase->exec_SELECTgetRows('*', 'sys_file_storage', '1=1', '', '', '', 'uid');

        $missingOnForeign = array_diff(array_keys($localStorages), array_keys($foreignStorages));
        $missingOnLocal = array_diff(array_keys($foreignStorages), array_keys($localStorages));

        $messages = array();

        if (!empty($missingOnLocal)) {
            $messages[] = 'fal.missing_on_local';
            foreach ($missingOnLocal as $storageUid) {
                $messages[] = $foreignStorages[$storageUid]['name'];
                unset($foreignStorages[$storageUid]);
            }
        }

        if (!empty($missingOnForeign)) {
            $messages[] = 'fal.missing_on_foreign';
            foreach ($missingOnForeign as $storageUid) {
                $messages[] = $localStorages[$storageUid]['name'];
                unset($localStorages[$storageUid]);
            }
        }

        $addPremiumNotice = false;
        foreach ($localStorages as $uid => $localStorage) {
            if ($foreignStorages[$uid]['driver'] !== $localStorage['driver']) {
                $messages[] = 'fal.different_storage_drivers';
                $messages[] = sprintf(
                    'Local: "%s" Foreign: "%s"',
                    $localStorage['name'],
                    $foreignStorages[$uid]['name']
                );
                $addPremiumNotice = true;
            }
        }

        if (true === $addPremiumNotice) {
            $messages[] = 'fal.xsp_premium_notice';
        }

        if (!empty($messages)) {
            return new TestResult('fal.storage_errors', TestResult::ERROR, $messages);
        }
        return new TestResult('fal.storage_okay');
    }

    /**
     * @return array List of test classes that need to pass before this test can be executed
     */
    public function getDependencies()
    {
        return array(
            'In2code\\In2publishCore\\Testing\\Tests\\Database\\LocalDatabaseTest',
            'In2code\\In2publishCore\\Testing\\Tests\\Database\\ForeignDatabaseTest',
        );
    }
}
