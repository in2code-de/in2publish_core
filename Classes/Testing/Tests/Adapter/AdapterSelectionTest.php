<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Testing\Tests\Adapter;

/*
 * Copyright notice
 *
 * (c) 2017 in2code.de and the following authors:
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

use In2code\In2publishCore\Communication\AdapterRegistry;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteAdapter\AdapterInterface as RemoteAdapter;
use In2code\In2publishCore\Communication\TemporaryAssetTransmission\TransmissionAdapter\AdapterInterface as TransAdapt;
use In2code\In2publishCore\In2publishCoreException;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function array_key_exists;
use function class_exists;
use function is_array;

/**
 * Class AdapterSelectionTest
 */
class AdapterSelectionTest implements TestCaseInterface
{
    /**
     * @var AdapterRegistry
     */
    protected $adapterRegistry;

    /**
     * AdapterSelectionTest constructor.
     */
    public function __construct()
    {
        $this->adapterRegistry = GeneralUtility::makeInstance(AdapterRegistry::class);
    }

    /**
     * @return TestResult
     */
    public function run(): TestResult
    {
        $config = $this->adapterRegistry->getConfig();
        if (!is_array($config) || !array_key_exists('remote', $config) || !array_key_exists('transmission', $config)) {
            return new TestResult('adapter.adapter_selection.config_error', TestResult::ERROR);
        }

        $missingAdapter = [];
        try {
            if (!class_exists($this->adapterRegistry->getAdapter(RemoteAdapter::class))) {
                $missingAdapter[] = 'remote';
            }
        } catch (In2publishCoreException $e) {
            $missingAdapter[] = 'remote';
        }
        try {
            if (!class_exists($this->adapterRegistry->getAdapter(TransAdapt::class))) {
                $missingAdapter[] = 'transmission';
            }
        } catch (In2publishCoreException $e) {
            $missingAdapter[] = 'transmission';
        }
        if (empty($missingAdapter)) {
            return new TestResult('adapter.adapter_selection.valid');
        }
        return new TestResult(
            'adapter.adapter_selection.missing_adapter_or_implementation',
            TestResult::ERROR,
            $missingAdapter
        );
    }

    /**
     * @return array
     */
    public function getDependencies(): array
    {
        return [];
    }
}
