<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Service\Environment;

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

use In2code\In2publishCore\Component\ConfigContainer\ConfigContainer;
use In2code\In2publishCore\Service\Environment\EnvironmentService;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Registry;

use function json_encode;
use function serialize;
use function sha1;

/**
 * @coversDefaultClass \In2code\In2publishCore\Service\Environment\EnvironmentService
 */
class EnvironmentServiceTest extends UnitTestCase
{
    /**
     * @covers ::getPackagesHash
     * @covers ::setTestResult
     */
    public function testSetTestResultStoresTestResultCurrentConfigAndPackagesHashIntoRegistry(): void
    {
        $packagesArray = ['foo' => ['bar' => ['baz' => 'buz']]];
        $packagesHash = sha1(json_encode($packagesArray));

        $configurationArray = ['boo' => ['far' => ['faz' => 'fuz']]];
        $configurationHash = sha1(serialize($configurationArray));

        /** @var Registry|MockObject $registry */
        $registry = $this->getMockBuilder(Registry::class)->onlyMethods(['set'])->getMock();
        $registry->expects($this->once())->method('set')->with(
            'tx_in2publishcore',
            'test_result',
            [
                'success' => true,
                'packages_hash' => $packagesHash,
                'configuration_hash' => $configurationHash,
            ],
        );

        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getActivePackages')->willReturn($packagesArray);

        $configContainer = $this->createMock(ConfigContainer::class);
        $configContainer->method('get')->willReturn($configurationArray);

        $environmentService = new EnvironmentService($packageManager);
        $environmentService->injectConfigContainer($configContainer);
        $environmentService->injectRegistry($registry);

        $environmentService->setTestResult(true);
    }

    public static function getResultRegistryReturnAndExpectedValueDataProvider(): array
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
        /** @var Registry|MockObject $registry */
        $registry = $this->createMock(Registry::class);
        $registry->expects($this->once())->method('get')->willReturn($registryReturn);

        $packagesArray = ['foo' => ['bar' => ['baz' => 'buz']]];

        $configurationArray = ['boo' => ['far' => ['faz' => 'fuz']]];

        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getActivePackages')->willReturn($packagesArray);

        $configContainer = $this->createMock(ConfigContainer::class);
        $configContainer->method('get')->willReturn($configurationArray);

        $environmentService = new EnvironmentService($packageManager);
        $environmentService->injectConfigContainer($configContainer);
        $environmentService->injectRegistry($registry);

        $this->assertSame($expected, $environmentService->getTestStatus());
    }
}
