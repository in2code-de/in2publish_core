<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Communication;

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

use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteAdapter\AdapterInterface as RceAdapter;
use In2code\In2publishCore\Communication\TemporaryAssetTransmission\TransmissionAdapter\AdapterInterface as TatAdapter;
use In2code\In2publishCore\In2publishCoreException;
use In2code\In2publishCore\Testing\Tests\Adapter\RemoteAdapterTest;
use In2code\In2publishCore\Testing\Tests\Adapter\TransmissionAdapterTest;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_column;
use function array_combine;
use function array_keys;
use function in_array;
use function is_subclass_of;

class AdapterRegistry implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var string[][][]
     */
    protected $adapter = [
        'remote' => [],
        'transmission' => [],
    ];

    protected $adapterMap = [
        'remote' => [
            'interface' => RceAdapter::class,
            'tester' => RemoteAdapterTest::class,
        ],
        'transmission' => [
            'interface' => TatAdapter::class,
            'tester' => TransmissionAdapterTest::class,
        ],
    ];

    protected $config = [
        'adapter' => [
            'remote' => 'ssh',
            'transmission' => 'ssh',
        ],
    ];

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct()
    {
        if (!isset($GLOBALS['in2publish_core']['tests'])) {
            $GLOBALS['in2publish_core']['tests'] = [];
        }
        $setConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('in2publish_core');
        ArrayUtility::mergeRecursiveWithOverrule($this->config, $setConf, false);
    }

    public function registerAdapter(string $type, string $key, string $adapter, string $label, array $tests = []): bool
    {
        if (!isset($this->adapterMap[$type])) {
            $this->logger->alert(
                'The adapter type was not registered',
                ['adapter' => $adapter, 'key' => $key, 'type' => $type]
            );
            return false;
        }

        $interface = $this->adapterMap[$type]['interface'];

        if (!is_subclass_of($adapter, $interface)) {
            $this->logger->critical(
                'The registered adapter does not implement the type interface',
                ['adapter' => $adapter, 'key' => $key, 'type' => $type, 'interface' => $interface]
            );
            return false;
        }

        $this->adapter[$type][$key] = [
            'class' => $adapter,
            'tests' => $tests,
            'label' => $label,
        ];

        if ($key === $this->config['adapter'][$type]) {
            $this->addTests($tests, $interface);
        }

        return true;
    }

    /** @return string[][] */
    public function getAdapterInfo(): array
    {
        $adapterInfo = [];
        foreach ($this->adapter as $type => $adapters) {
            foreach ($adapters as $key => $config) {
                $label = $this->getLanguageService()->sL($config['label']);
                $adapterInfo[$type][$key] = $key . ': ' . ($label ?: $config['label']);
            }
        }
        return $adapterInfo;
    }

    /** @throws In2publishCoreException */
    public function getAdapter(string $interface): ?string
    {
        $interfaceTypeMap = array_combine(array_column($this->adapterMap, 'interface'), array_keys($this->adapterMap));
        if (!isset($interfaceTypeMap[$interface])) {
            $this->logger->alert('Adapter type is not available', ['interface' => $interface]);
        } else {
            $type = $interfaceTypeMap[$interface];
            if (!isset($this->adapter[$type][$this->config['adapter'][$type]]['class'])) {
                $this->logger->critical('No adapter was registered for the requested type', ['type' => $type]);
            } else {
                return $this->adapter[$type][$this->config['adapter'][$type]]['class'];
            }
        }
        throw new In2publishCoreException('Could not determine adapter or type for ' . $interface, 1507906038);
    }

    /** @SuppressWarnings(PHPMD.Superglobals) */
    protected function addTests(array $tests, string $interface): void
    {
        $GLOBALS['in2publish_core']['virtual_tests'][$interface] = $tests;
        foreach ($tests as $test) {
            if (!in_array($test, $GLOBALS['in2publish_core']['tests'])) {
                $GLOBALS['in2publish_core']['tests'][] = $test;
            }
        }
    }

    public function getConfig(): array
    {
        return $this->config['adapter'];
    }

    /** @SuppressWarnings(PHPMD.Superglobals) */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
