<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Config\PostProcessor;

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

use In2code\In2publishCore\Config\PostProcessor\DynamicValueProvider\DynamicValueProviderRegistry;
use In2code\In2publishCore\Config\PostProcessor\DynamicValueProvider\Exception\InvalidDynamicValueProviderKeyException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

use function is_array;
use function is_string;
use function preg_match;
use function strlen;

/**
 * Allows the dynamic evaluation and replacement of configuration values.
 * The actual value comes from the provider registered with the shortcut name.
 * Register provider at the registry.
 */
class DynamicValuesPostProcessor implements PostProcessorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const DYNAMIC_REFERENCE_PATTERN = '/^%(?P<key>[\w]+)\((?P<string>[^\)]*)\)%$/';

    /** @var DynamicValueProviderRegistry */
    protected $dynamicValueProviderRegistry;

    protected $rtc = [];

    public function __construct(DynamicValueProviderRegistry $dynamicValueProviderRegistry)
    {
        $this->dynamicValueProviderRegistry = $dynamicValueProviderRegistry;
    }

    /** @throws InvalidDynamicValueProviderKeyException */
    public function process(array $config): array
    {
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $config[$key] = $this->process($value);
            } elseif (is_string($value) && strlen($value) > 3) {
                $matches = [];
                if (1 === preg_match(self::DYNAMIC_REFERENCE_PATTERN, $value, $matches)) {
                    $providerKey = $matches['key'];
                    $providerString = $matches['string'];
                    if (!$this->dynamicValueProviderRegistry->hasDynamicValueProviderForKey($providerKey)) {
                        $this->logMissingDynamicValueProvider($providerKey);
                    } else {
                        $provider = $this->dynamicValueProviderRegistry->getDynamicValueProviderByKey($providerKey);
                        $value = $provider->getValueFor($providerString);
                        $config[$key] = $value;
                    }
                }
            }
        }
        return $config;
    }

    /**
     * Logs missing provider keys. Only once per request to avoid log flooding.
     *
     * @param string $providerKey
     */
    protected function logMissingDynamicValueProvider(string $providerKey)
    {
        if (!$this->rtc['missing'][$providerKey]) {
            $this->rtc['missing'][$providerKey] = true;
            $this->logger->error(
                'Identified dynamic configuration provider key but no provider was registered for it',
                ['providerKey' => $providerKey]
            );
        }
    }
}
