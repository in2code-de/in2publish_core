<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\RemoteCommandExecution\RemoteAdapter;

/*
 * Copyright notice
 *
 * (c) 2023 in2code.de and the following authors:
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

use RuntimeException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_key_exists;
use function in_array;
use function sprintf;
use function user_error;

use const E_USER_DEPRECATED;

class RemoteAdapterRegistry
{
    protected const DEPRECATION_MANUAL_REGISTRATION = 'The manual registration of remote adapter is deprecated and will be removed in in2publish_core. Tag your adapter with in2publish_core.adapter.remote_adapter and the key %s instead.';
    private array $adapters;
    private array $legacyAdapters;
    private string $selectedAdapter;

    /**
     * @param array<string, class-string<AdapterInterface>> $adapters
     */
    public function __construct(ExtensionConfiguration $extensionConfiguration, array $adapters)
    {
        $this->selectedAdapter = $extensionConfiguration->get('in2publish_core', 'adapter/remote');
        $this->adapters = $adapters;
        if (isset($this->adapters[$this->selectedAdapter])) {
            $this->addTests($this->adapters[$this->selectedAdapter]['tests'], AdapterInterface::class);
        }
    }

    /**
     * @deprecated The manual registration of remote adapter is deprecated and will be removed in in2publish_core. Use
     *     tags instead.
     */
    public function registerAdapter(string $identifier, string $class, string $label, array $tests = []): bool
    {
        user_error(sprintf(self::DEPRECATION_MANUAL_REGISTRATION, $identifier), E_USER_DEPRECATED);
        $this->legacyAdapters[$identifier] = [
            'class' => $class,
            'tests' => $tests,
            'label' => $label,
        ];

        if ($identifier === $this->selectedAdapter) {
            $this->addTests($tests, AdapterInterface::class);
        }

        return true;
    }

    protected function addTests(array $tests, string $interface): void
    {
        $GLOBALS['in2publish_core']['virtual_tests'][$interface] = $tests;
        foreach ($tests as $test) {
            if (
                empty($GLOBALS['in2publish_core']['tests'])
                || !in_array($test, $GLOBALS['in2publish_core']['tests'], true)
            ) {
                $GLOBALS['in2publish_core']['tests'][] = $test;
            }
        }
    }

    public function getAdapterRegistration(string $identifier): ?array
    {
        return $this->legacyAdapters[$identifier] ?? null;
    }

    public function getSelectedAdapter(): string
    {
        return $this->selectedAdapter;
    }

    public function createSelectedAdapter(): AdapterInterface
    {
        if (array_key_exists($this->selectedAdapter, $this->adapters)) {
            return GeneralUtility::makeInstance($this->adapters[$this->selectedAdapter]['class']);
        }
        if (!array_key_exists($this->selectedAdapter, $this->legacyAdapters)) {
            throw new RuntimeException(
                "Could not create remote adapter '$this->selectedAdapter': Adapter not found",
                1657115622,
            );
        }
        return GeneralUtility::makeInstance($this->legacyAdapters[$this->selectedAdapter]['class']);
    }
}
