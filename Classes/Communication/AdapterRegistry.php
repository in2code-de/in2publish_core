<?php
namespace In2code\In2publishCore\Communication;

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

use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteAdapter\AdapterInterface as RemoteAdapter;
use In2code\In2publishCore\Communication\TemporaryAssetTransmission\TransmissionAdapter\AdapterInterface
    as TransmissionAdapter;
use In2code\In2publishCore\In2publishCoreException;
use In2code\In2publishCore\Testing\Data\ConfigurationDefinitionProvider;
use In2code\In2publishCore\Testing\Tests\Adapter\RemoteAdapterTest;
use In2code\In2publishCore\Testing\Tests\Adapter\TransmissionAdapterTest;
use In2code\In2publishCore\Utility\ConfigurationUtility;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Class AdapterRegistry
 */
class AdapterRegistry implements SingletonInterface
{
    /**
     * @var string[][][]
     */
    protected $adapter = [
        'remote' => [],
        'transmission' => [],
    ];

    /**
     * @var array
     */
    protected $adapterMap = [
        'remote' => [
            'interface' => RemoteAdapter::class,
            'tester' => RemoteAdapterTest::class,
        ],
        'transmission' => [
            'interface' => TransmissionAdapter::class,
            'tester' => TransmissionAdapterTest::class,
        ],
    ];

    /**
     * @var LoggerInterface
     */
    protected $logger = null;

    /**
     * @var Dispatcher
     */
    protected $signalSlotDispatcher = null;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * AdapterRegistry constructor.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $this->signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);
        $this->config = ConfigurationUtility::getConfiguration('adapter');
        if (!isset($GLOBALS['in2publish_core']['tests'])) {
            $GLOBALS['in2publish_core']['tests'] = [];
        }
    }

    /**
     * @param string $type
     * @param string $key
     * @param string $adapter
     * @param string $label
     * @param array $provider
     * @param array $tests
     *
     * @return bool
     */
    public function registerAdapter($type, $key, $adapter, $label, array $provider = [], array $tests = [])
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
            'provider' => $provider,
            'label' => $label,
        ];

        if ($key === $this->config[$type]) {
            $this->addTests($tests, $interface);
            $this->registerProvider($provider);
        }

        return true;
    }

    /**
     * @return string[][]
     */
    public function getAdapterInfo()
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

    /**
     * @param string $interface
     *
     * @return null|string
     *
     * @throws In2publishCoreException
     */
    public function getAdapter($interface)
    {
        $interfaceTypeMap = array_combine(array_column($this->adapterMap, 'interface'), array_keys($this->adapterMap));
        if (!isset($interfaceTypeMap[$interface])) {
            $this->logger->alert('Adapter type is not available', ['interface' => $interface]);
        } else {
            $type = $interfaceTypeMap[$interface];
            if (!isset($this->adapter[$type][$this->config[$type]]['class'])) {
                $this->logger->critical('No adapter was registered for the requested type', ['type' => $type]);
            } else {
                return $this->adapter[$type][$this->config[$type]]['class'];
            }
        }
        throw new In2publishCoreException('Could not determine adapter or type for ' . $interface, 1507906038);
    }

    /**
     * @param array $provider
     */
    protected function registerProvider(array $provider)
    {
        foreach ($provider as $class => $method) {
            if (!$this->isSlotRegistered($class, $method)) {
                $this->signalSlotDispatcher->connect(
                    ConfigurationDefinitionProvider::class,
                    'overruleDefinition',
                    $class,
                    $method,
                    false
                );
            }
        }
    }

    /**
     * @param string $class
     * @param string $method
     *
     * @return bool
     */
    protected function isSlotRegistered($class, $method)
    {
        $slots = $this->signalSlotDispatcher->getSlots(ConfigurationDefinitionProvider::class, 'overruleDefinition');
        foreach ($slots as $slot) {
            if ($slot['class'] === $class && $slot['method'] === $method) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array $tests
     * @param string $interface
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function addTests(array $tests, $interface)
    {
        $GLOBALS['in2publish_core']['virtual_tests'][$interface] = $tests;
        foreach ($tests as $test) {
            if (!in_array($test, $GLOBALS['in2publish_core']['tests'])) {
                $GLOBALS['in2publish_core']['tests'][] = $test;
            }
        }
    }

    /**
     * @return LanguageService
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
