<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\In2code\In2publishCore\Service\Environment;

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

use Codeception\Test\Unit;
use In2code\In2publishCore\Service\Environment\EnvironmentService;
use In2code\In2publishCore\Tests\UnitTester;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Registry;

/**
 * @coversDefaultClass \In2code\In2publishCore\Service\Environment\EnvironmentService
 */
class EnvironmentServiceTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before(): void
    {
        $this->tester->setUp();
    }

    protected function _after(): void
    {
        $this->tester->tearDown();
    }

    /**
     * @covers ::__construct
     * @covers ::getPackagesHash
     * @covers ::setTestResult
     */
    public function testSetTestResultStoresTestResultCurrentConfigAndPackagesHashIntoRegistry(): void
    {
        /** @var EnvironmentService|MockObject $environmentService */
        $environmentService = $this->getMockBuilder(EnvironmentService::class)->setMethods(
            ['getActivePackagesArray', 'getConfigurationHash', 'getRegistry']
        )->disableOriginalConstructor()->getMock();

        $packagesArray = ['foo' => ['bar' => ['baz' => 'buz']]];
        $packagesHash = sha1(json_encode($packagesArray));

        $configurationArray = ['boo' => ['far' => ['faz' => 'fuz']]];
        $configurationHash = sha1(serialize($configurationArray));

        /** @var Registry|MockObject $registry */
        $registry = $this->getMockBuilder(Registry::class)->setMethods(['set'])->getMock();
        $registry->expects($this->once())->method('set')->with(
            'tx_in2publishcore',
            'test_result',
            [
                'success' => true,
                'packages_hash' => $packagesHash,
                'configuration_hash' => $configurationHash,
            ]
        );

        $environmentService->method('getRegistry')->willReturn($registry);
        $environmentService->method('getActivePackagesArray')->willReturn($packagesArray);
        $environmentService->method('getConfigurationHash')->willReturn($configurationHash);

        $environmentService->__construct();

        $environmentService->setTestResult(true);
    }

    /**
     *
     */
    public function getResultRegistryReturnAndExpectedValueDataProvider(): array
    {
        $configurationHash = sha1(serialize(['boo' => ['far' => ['faz' => 'fuz']]]));
        $packagesHash = sha1(json_encode(['foo' => ['bar' => ['baz' => 'buz']]]));

        return [
            'tests_never_ran' => [
                [
                    EnvironmentService::STATE_TESTS_NEVER_RAN,
                ],
                false,
            ],
            'only_package_states_wrong' => [
                [
                    EnvironmentService::STATE_PACKAGES_CHANGED,
                ],
                [
                    'packages_hash' => 'other',
                    'configuration_hash' => $configurationHash,
                    'success' => true,
                ],
            ],
            'only_configuration_hash_wrong' => [
                [
                    EnvironmentService::STATE_CONFIGURATION_CHANGED,
                ],
                [
                    'packages_hash' => $packagesHash,
                    'configuration_hash' => 'other',
                    'success' => true,
                ],
            ],
            'only_tests_failed' => [
                [
                    EnvironmentService::STATE_TESTS_FAILING,
                ],
                [
                    'packages_hash' => $packagesHash,
                    'configuration_hash' => $configurationHash,
                    'success' => false,
                ],
            ],
            'tests_failed_and_packages_changed' => [
                [
                    EnvironmentService::STATE_PACKAGES_CHANGED,
                    EnvironmentService::STATE_TESTS_FAILING,
                ],
                [
                    'packages_hash' => 'other',
                    'configuration_hash' => $configurationHash,
                    'success' => false,
                ],
            ],
            'tests_failed_and_configuration_changed' => [
                [
                    EnvironmentService::STATE_CONFIGURATION_CHANGED,
                    EnvironmentService::STATE_TESTS_FAILING,
                ],
                [
                    'packages_hash' => $packagesHash,
                    'configuration_hash' => 'other',
                    'success' => false,
                ],
            ],
            'tests_failed_and_packages_and_config_changed' => [
                [
                    EnvironmentService::STATE_PACKAGES_CHANGED,
                    EnvironmentService::STATE_CONFIGURATION_CHANGED,
                    EnvironmentService::STATE_TESTS_FAILING,
                ],
                [
                    'packages_hash' => 'other',
                    'configuration_hash' => 'other',
                    'success' => false,
                ],
            ],
            'all_good' => [
                [],
                [
                    'packages_hash' => $packagesHash,
                    'configuration_hash' => $configurationHash,
                    'success' => true,
                ],
            ],
        ];
    }

    /**
     * @covers ::getTestStatus
     * @dataProvider getResultRegistryReturnAndExpectedValueDataProvider
     *
     * @param $expected
     * @param $registryReturn
     */
    public function testGetTestResultReturnsExpectedValue($expected, $registryReturn): void
    {
        /** @var EnvironmentService|MockObject $environmentService */
        $environmentService = $this->getMockBuilder(EnvironmentService::class)->setMethods(
            ['getActivePackagesArray', 'getConfigurationHash', 'getRegistry']
        )->disableOriginalConstructor()->getMock();

        $packagesArray = ['foo' => ['bar' => ['baz' => 'buz']]];

        $configurationArray = ['boo' => ['far' => ['faz' => 'fuz']]];
        $configurationHash = sha1(serialize($configurationArray));

        /** @var Registry|MockObject $registry */
        $registry = $this->getMockBuilder(Registry::class)->setMethods(['get'])->getMock();
        $registry->expects($this->once())->method('get')->willReturn($registryReturn);

        $environmentService->method('getRegistry')->willReturn($registry);
        $environmentService->method('getActivePackagesArray')->willReturn($packagesArray);
        $environmentService->method('getConfigurationHash')->willReturn($configurationHash);

        $environmentService->__construct();

        $this->assertSame($expected, $environmentService->getTestStatus());
    }
}
