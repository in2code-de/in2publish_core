<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\ConfigContainer;

/*
 * Copyright notice
 *
 * (c) 2018 in2code.de and the following authors:
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

use In2code\In2publishCore\Component\ConfigContainer\Definer\ConditionalDefinerInterface;
use In2code\In2publishCore\Component\ConfigContainer\Definer\DefinerInterface;
use In2code\In2publishCore\Component\ConfigContainer\Migration\MigrationInterface;
use In2code\In2publishCore\Component\ConfigContainer\Node\Node;
use In2code\In2publishCore\Component\ConfigContainer\Node\NodeCollection;
use In2code\In2publishCore\Component\ConfigContainer\PostProcessor\PostProcessorInterface;
use In2code\In2publishCore\Component\ConfigContainer\Provider\ConditionalProviderInterface;
use In2code\In2publishCore\Component\ConfigContainer\Provider\ContextualProvider;
use In2code\In2publishCore\Component\ConfigContainer\Provider\ProviderInterface;
use In2code\In2publishCore\Service\Context\ContextServiceInjection;
use In2code\In2publishCore\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_combine;
use function array_fill;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function asort;
use function count;
use function explode;
use function is_array;
use function trim;

class ConfigContainer implements SingletonInterface
{
    use ContextServiceInjection;

    /** @var array<class-string<ProviderInterface>, ProviderInterface|null|false> */
    protected array $providers = [];
    /** @var array<class-string<ProviderInterface>, array> */
    protected array $providerConfig = [];
    /** @var array<class-string<DefinerInterface>, DefinerInterface|null|false> */
    protected array $definers = [];
    /** @var array<class-string<PostProcessorInterface>, PostProcessorInterface|null> */
    protected array $postProcessors = [];
    /** @var array<class-string<MigrationInterface>, MigrationInterface|null> */
    protected array $migrations = [];
    protected ?array $config = null;
    /** @var NodeCollection[]|null[] */
    protected array $definition = [
        'local' => null,
        'foreign' => null,
    ];

    /** @return mixed */
    public function get(string $path = '')
    {
        $config = $this->getConfig();
        /** @noinspection DuplicatedCode */
        $path = trim($path, " \t\n\r\0\x0B.");
        if (!empty($path)) {
            foreach (explode('.', $path) as $key) {
                if (!is_array($config)) {
                    return null;
                }
                if (!array_key_exists($key, $config)) {
                    return null;
                }
                $config = $config[$key];
            }
        }
        return $config;
    }

    protected function getConfig(): array
    {
        if (null !== $this->config) {
            return $this->config;
        }
        $this->initializeProviderObjects();

        $complete = true;
        $priority = [];
        foreach (array_filter($this->providers) as $class => $provider) {
            $priority[$class] = $provider->getPriority();
            if (!array_key_exists($class, $this->providerConfig)) {
                if ($provider->isAvailable()) {
                    $this->providerConfig[$class] = $provider->getConfig();
                } else {
                    $complete = false;
                }
            }
        }

        $config = $this->processConfig($priority);

        if (true === $complete) {
            $this->config = $config;
        }

        return $config;
    }

    /**
     * Returns the configuration without any contextual parts.
     * Is always "fresh" but never guaranteed to be complete.
     */
    public function getContextFreeConfig(): array
    {
        $this->initializeProviderObjects();

        $priority = [];
        foreach (array_filter($this->providers) as $class => $provider) {
            if ($provider instanceof ContextualProvider) {
                continue;
            }
            if (!array_key_exists($class, $this->providerConfig) && $provider->isAvailable()) {
                $this->providerConfig[$class] = $provider->getConfig();
            }
            $priority[$class] = $provider->getPriority();
        }

        return $this->processConfig($priority);
    }

    /**
     * Applies the configuration of each provider in order of priority.
     *
     * @return array|array[]|bool[]|int[]|string[] Sorted, merged and type cast configuration.
     */
    protected function processConfig(array $priority): array
    {
        asort($priority);

        $config = [];
        foreach (array_keys($priority) as $class) {
            $providerConfig = $this->providerConfig[$class] ?? null;
            if (null !== $providerConfig) {
                $config = ConfigurationUtility::mergeConfiguration($config, $providerConfig);
            }
        }

        foreach ($this->postProcessors as $class => $object) {
            if (null === $object) {
                $object = GeneralUtility::makeInstance($class);
                $this->postProcessors[$class] = $object;
            }
            if ($object instanceof PostProcessorInterface) {
                $config = $object->process($config);
            }
        }

        $config = $this->migrateConfig($config);

        if ($this->contextService->isLocal()) {
            $config = $this->getLocalDefinition()->cast($config);
        } else {
            $config = $this->getForeignDefinition()->cast($config);
        }

        return $config;
    }

    protected function migrateConfig(array $config): array
    {
        foreach ($this->migrations as $class => $migration) {
            if (null === $migration) {
                $this->migrations[$class] = $migration = GeneralUtility::makeInstance($class);
            }

            $config = $migration->migrate($config);
        }
        return $config;
    }

    public function getLocalDefinition(string $path = ''): Node
    {
        $this->initializeDefinerObjects();

        if (null === $this->definition['local']) {
            $definition = GeneralUtility::makeInstance(NodeCollection::class);
            foreach (array_filter($this->definers) as $definer) {
                $definition->addNodes($definer->getLocalDefinition());
            }
            $this->definition['local'] = $definition;
        }
        return $this->definition['local']->getNodePath($path);
    }

    public function getForeignDefinition(string $path = ''): Node
    {
        $this->initializeDefinerObjects();

        if (null === $this->definition['foreign']) {
            $definition = GeneralUtility::makeInstance(NodeCollection::class);
            foreach (array_filter($this->definers) as $definer) {
                $definition->addNodes($definer->getForeignDefinition());
            }
            $this->definition['foreign'] = $definition;
        }
        return $this->definition['foreign']->getNodePath($path);
    }

    /**
     * All providers must be registered in ext_localconf.php!
     * Providers registered in ext_tables.php will not overrule configurations of already loaded extensions.
     * Providers must implement the ProviderInterface, or they won't be called.
     */
    public function registerProvider(string $provider): void
    {
        $this->providers[$provider] = null;
    }

    /**
     * All definers must be registered in ext_localconf.php!
     * Definers must implement the DefinerInterface, or they won't be called.
     */
    public function registerDefiner(string $definer): void
    {
        $this->definers[$definer] = null;
    }

    /**
     * All post processors must be registered in ext_localconf.php!
     * PostProcessors must implement the PostProcessorInterface, or they won't be called.
     */
    public function registerPostProcessor(string $postProcessor): void
    {
        $this->postProcessors[$postProcessor] = null;
    }

    /**
     * All migrations must be registered in ext_localconf.php!
     * Migrations must implement the MigrationInterface.
     */
    public function registerMigration(string $migration): void
    {
        $this->migrations[$migration] = null;
    }

    public function getMigrationMessages(): array
    {
        $messages = [];
        foreach ($this->migrations as $class => $migration) {
            if (null === $migration) {
                $this->migrations[$class] = $migration = GeneralUtility::makeInstance($class);
            }
            $messages[] = $migration->getMessages();
        }
        return array_merge([], ...$messages);
    }

    /**
     * Returns the information about all registered classes which are responsible for the resulting configuration.
     */
    public function dump(): array
    {
        // Clone this instance and reset it
        $cloned = clone $this;
        $cloned->config = null;
        $cloned->definers = array_combine(array_keys($this->definers), array_fill(0, count($this->definers), null));
        $cloned->postProcessors = array_combine(
            array_keys($this->postProcessors),
            array_fill(0, count($this->postProcessors), null),
        );
        $fullConfig = $cloned->get();

        $priority = [];
        foreach (array_keys($cloned->providers) as $class) {
            $provider = GeneralUtility::makeInstance($class);
            if ($provider instanceof ProviderInterface) {
                $priority[$class] = $provider->getPriority();
            }
        }

        asort($priority);

        $orderedProviderConfig = [];
        foreach (array_keys($priority) as $class) {
            $orderedProviderConfig[$class] = $cloned->providerConfig[$class];
        }

        return [
            'fullConfig' => $fullConfig,
            'providers' => $orderedProviderConfig,
            'definers' => array_keys($cloned->definers),
            'postProcessors' => array_keys($cloned->postProcessors),
            'migrations' => $cloned->migrations,
        ];
    }

    protected function initializeProviderObjects(): void
    {
        foreach ($this->providers as $class => $object) {
            if (null === $object) {
                $provider = GeneralUtility::makeInstance($class);
                // If the Provider does not implement the ProviderInterface, it will be skipped (set the default)
                $this->providers[$class] = false;
                if ($provider instanceof ProviderInterface) {
                    $this->providers[$class] = $provider;
                    if ($provider instanceof ConditionalProviderInterface && !$provider->isEnabled()) {
                        // Preset the provider's config to never ask it again
                        $this->providerConfig[$class] = null;
                    }
                }
            }
        }
    }

    protected function initializeDefinerObjects(): void
    {
        foreach ($this->definers as $class => $definer) {
            if (null === $definer) {
                $definer = GeneralUtility::makeInstance($class);
                $this->definers[$class] = false;
                if ($definer instanceof DefinerInterface) {
                    if (
                        $definer instanceof ConditionalDefinerInterface
                        && !$definer->isEnabled()
                    ) {
                        continue;
                    }
                    $this->definers[$class] = $definer;
                }
            }
        }
    }
}
