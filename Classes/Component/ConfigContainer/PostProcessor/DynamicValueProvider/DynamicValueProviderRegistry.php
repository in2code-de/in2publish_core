<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\ConfigContainer\PostProcessor\DynamicValueProvider;

/*
 * Copyright notice
 *
 * (c) 2020 in2code.de and the following authors:
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

use In2code\In2publishCore\Component\ConfigContainer\PostProcessor\DynamicValueProvider\Exception\InvalidDynamicValueProviderKeyException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function sprintf;
use function user_error;

use const E_USER_DEPRECATED;

class DynamicValueProviderRegistry implements SingletonInterface
{
    protected const DEPRECATED_MANUAL_REGISTRATION = 'Manual registration of DynamicValueProvider is deprecated. Implement DynamicValueProviderInterface in %s instead and return %s as the key. This method and the interface will be removed in in2publish_core v13.';
    /** @var array<DynamicValueProviderServiceInterface> */
    protected array $providers = [];

    /**
     * @param array<DynamicValueProviderServiceInterface> $providers
     */
    public function __construct(array $providers)
    {
        foreach ($providers as $provider) {
            $this->providers[$provider->getKey()] = $provider;
        }
    }

    /**
     * @param string $key The key which will be used in the configuration to call the registered provider
     * @param class-string<DynamicValueProviderInterface> $class The FQCN of the provider. Must implement
     *     `DynamicValueProviderInterface`.
     *
     * @deprecated Implement DynamicValueProviderServiceInterface in your provider to automatically register it.
     */
    public function registerDynamicValue(string $key, string $class): void
    {
        user_error(sprintf(self::DEPRECATED_MANUAL_REGISTRATION, $class, $key), E_USER_DEPRECATED);
        if (!isset($this->providers[$key])) {
            $this->providers[$key] = GeneralUtility::makeInstance($class);
        }
    }

    public function getRegisteredClasses(): array
    {
        return $this->providers;
    }

    public function hasDynamicValueProviderForKey(string $key): bool
    {
        return isset($this->providers[$key]);
    }

    public function getDynamicValueProviderByKey(string $key): DynamicValueProviderInterface
    {
        if (!$this->hasDynamicValueProviderForKey($key)) {
            throw new InvalidDynamicValueProviderKeyException($key);
        }
        return $this->providers[$key];
    }
}
