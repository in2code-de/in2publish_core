<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Testing\Tests\Fal;

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

use In2code\In2publishCore\Testing\Data\FalStorageTestSubjectsProvider;
use In2code\In2publishCore\Testing\Tests\Database\ForeignDatabaseTest;
use In2code\In2publishCore\Testing\Tests\Database\LocalDatabaseTest;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function array_diff;
use function array_keys;
use function sprintf;

/**
 * Class MissingStoragesTest
 */
class MissingStoragesTest implements TestCaseInterface
{
    /**
     * @var FalStorageTestSubjectsProvider
     */
    protected $testSubjectProvider;

    /**
     * MissingStoragesTest constructor.
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
        $storages = $this->testSubjectProvider->getStoragesForMissingStoragesTest();
        $messages = $this->getMissing($storages, [], 'local');
        $messages = $this->getMissing($storages, $messages, 'foreign');

        if (!empty($messages)) {
            return new TestResult('fal.missing_storages', TestResult::ERROR, $messages);
        }

        return new TestResult('fal.no_missing_storages');
    }

    /**
     * @return array
     */
    public function getDependencies(): array
    {
        return [
            LocalDatabaseTest::class,
            ForeignDatabaseTest::class,
        ];
    }

    /**
     * @param array $storages
     * @param array $messages
     * @param string $side
     *
     * @return array
     */
    protected function getMissing(array $storages, array $messages, string $side): array
    {
        $opposite = $side === 'local' ? 'foreign' : 'local';
        $missingOnSide = array_diff(array_keys($storages[$opposite]), array_keys($storages[$side]));
        if (!empty($missingOnSide)) {
            // fal.missing_on_local | fal.missing_on_foreign
            $messages[] = 'fal.missing_on_' . $side;
            foreach ($missingOnSide as $storageUid) {
                $messages[] = sprintf('[%d] %s', $storageUid, $storages[$opposite][$storageUid]['name']);
            }
        }
        return $messages;
    }
}
