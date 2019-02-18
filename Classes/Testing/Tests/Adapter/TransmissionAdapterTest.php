<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Testing\Tests\Adapter;

/***************************************************************
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
 ***************************************************************/

use In2code\In2publishCore\Communication\TemporaryAssetTransmission\TransmissionAdapter\AdapterInterface;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use function array_merge;

/**
 * Class TransmissionAdapterTest
 */
class TransmissionAdapterTest implements TestCaseInterface
{
    /**
     * @return TestResult
     */
    public function run(): TestResult
    {
        return new TestResult('adapter.all_transmission_adapter_tests__passed');
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function getDependencies(): array
    {
        $dependencies = [
            AdapterSelectionTest::class,
        ];
        if (isset($GLOBALS['in2publish_core']['virtual_tests'][AdapterInterface::class])) {
            $dependencies = array_merge(
                $dependencies,
                $GLOBALS['in2publish_core']['virtual_tests'][AdapterInterface::class]
            );
        }
        return $dependencies;
    }
}
