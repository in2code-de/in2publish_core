<?php
declare(strict_types=1);
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

use In2code\In2publishCore\Testing\Data\FalStorageTestSubjectsProvider;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class IdenticalDriverTest
 */
class IdenticalDriverTest implements TestCaseInterface
{
    /**
     * @var FalStorageTestSubjectsProvider
     */
    protected $testSubjectProvider;

    /**
     * IdenticalDriverTest constructor.
     */
    public function __construct()
    {
        $this->testSubjectProvider = GeneralUtility::makeInstance(FalStorageTestSubjectsProvider::class);
    }

    /**
     * @return TestResult
     */
    public function run(): TestResult
    {
        $storages = $this->testSubjectProvider->getStoragesForDriverTest();
        $keys = array_unique(array_merge(array_keys($storages['local']), array_keys($storages['foreign'])));
        $affected = [];

        foreach ($keys as $key) {
            if (!isset($storages['local'][$key]['driver'], $storages['foreign'][$key]['driver'])) {
                continue;
            }

            if ($storages['local'][$key]['driver'] !== $storages['foreign'][$key]['driver']) {
                $affected[] = sprintf(
                    '[%d] %s (Local: %s; Foreign: %s)',
                    $key,
                    $storages['local'][$key]['name'],
                    $storages['local'][$key]['driver'],
                    $storages['foreign'][$key]['driver']
                );
            }
        }

        if (!empty($affected)) {
            $explanations = ['fal.driver_mismatch_explanation'];
            if (!ExtensionManagementUtility::isLoaded('in2publish')) {
                $explanations[] = 'fal.xsp_driver_premium_notice';
            }
            return new TestResult(
                'fal.driver_mismatch',
                TestResult::ERROR,
                array_merge(['fal.affected_storages'], $affected, $explanations)
            );
        }

        return new TestResult('fal.driver_matching');
    }

    /**
     * @return array
     */
    public function getDependencies(): array
    {
        return [
            CaseSensitivityTest::class,
        ];
    }
}
