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
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_keys;
use function array_merge;
use function array_unique;
use function sprintf;

class CaseSensitivityTest implements TestCaseInterface
{
    /**
     * @var FlexFormService
     */
    protected $flexFormService;

    /**
     * @var FalStorageTestSubjectsProvider
     */
    protected $testSubjectProvider;

    /**
     * CaseSensitivityTest constructor.
     */
    public function __construct()
    {
        $this->flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
        $this->testSubjectProvider = GeneralUtility::makeInstance(FalStorageTestSubjectsProvider::class);
    }

    /**
     * @return TestResult
     */
    public function run(): TestResult
    {
        $storages = $this->testSubjectProvider->getStoragesForCaseSensitivityTest();
        $keys = array_unique(array_merge(array_keys($storages['local']), array_keys($storages['foreign'])));
        $affected = [];

        foreach ($keys as $key) {
            $local = $this->getConfiguration($storages, $key, 'local');
            $foreign = $this->getConfiguration($storages, $key, 'foreign');
            if (
                isset($local['caseSensitive'], $foreign['caseSensitive'])
                && $local['caseSensitive'] !== $foreign['caseSensitive']
            ) {
                $affected[] = sprintf(
                    '[%d] %s (Local: %s; Foreign: %s)',
                    $keys,
                    $storages['local'][$key]['name'],
                    $local['caseSensitive'] ? 'true' : 'false',
                    $foreign['caseSensitive'] ? 'true' : 'false'
                );
            }
        }

        if (!empty($affected)) {
            return new TestResult(
                'fal.case_sensitivity_mismatch',
                TestResult::ERROR,
                array_merge(['fal.affected_storages'], $affected)
            );
        }

        return new TestResult('fal.case_sensitivity_matching');
    }

    /**
     * @return array
     */
    public function getDependencies(): array
    {
        return [
            MissingStoragesTest::class,
        ];
    }

    /**
     * @param array $storages
     * @param int $key
     * @param string $side
     *
     * @return array
     */
    protected function getConfiguration(array $storages, int $key, string $side): array
    {
        return $this->flexFormService->convertFlexFormContentToArray($storages[$side][$key]['configuration']);
    }
}
