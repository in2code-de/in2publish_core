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

use In2code\In2publishCore\Component\ConfigContainer\Definer\DefinerInterface;
use In2code\In2publishCore\Component\ConfigContainer\Definer\DefinerServiceInterface;
use In2code\In2publishCore\Component\ConfigContainer\Migration\MigrationInterface;
use In2code\In2publishCore\Component\ConfigContainer\Migration\MigrationServiceInterface;
use In2code\In2publishCore\Component\ConfigContainer\Node\Node;
use In2code\In2publishCore\Component\ConfigContainer\Node\NodeCollection;
use In2code\In2publishCore\Component\ConfigContainer\PostProcessor\PostProcessorInterface;
use In2code\In2publishCore\Component\ConfigContainer\PostProcessor\PostProcessorServiceInterface;
use In2code\In2publishCore\Component\ConfigContainer\Provider\ContextualProvider;
use In2code\In2publishCore\Component\ConfigContainer\Provider\ProviderInterface;
use In2code\In2publishCore\Component\ConfigContainer\Provider\ProviderServiceInterface;
use In2code\In2publishCore\Service\Context\ContextServiceInjection;
use In2code\In2publishCore\Utility\ConfigurationUtility;
use JsonException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_key_exists;
use function array_keys;
use function array_merge;
use function asort;
use function explode;
use function is_array;
use function json_encode;
use function sha1;
use function trim;

use const JSON_THROW_ON_ERROR;

class ConfigContainer implements SingletonInterface
{
    use ContextServiceInjection;
    use DeprecatedConfigContainer;

    /** @var array<class-string<ProviderInterface>, array> */
    protected array $providerConfig = [];
    protected ?array $config = null;
    protected array $incompleteConfig = [];
    /** @var NodeCollection[]|null[] */
    protected array $definition = [
        'local' => null,
        'foreign' => null,
    ];
    /** @var array<class-string<ProviderServiceInterface>, ProviderServiceInterface> */
    private array $providerServices;
    /** @var array<class-string<DefinerServiceInterface>, DefinerServiceInterface> */
    private array $definerServices;
    /** @var array<class-string<MigrationServiceInterface>, MigrationServiceInterface> */
    private array $migrationServices;
    /** @var array<class-string<PostProcessorServiceInterface>, PostProcessorServiceInterface> */
    private array $postProcessorServices;

    public function __construct(
        array $providerServices,
        array $definerServices,
        array $migrationServices,
        array $postProcessorServices
    ) {
        $this->providerServices = $providerServices;
        $this->definerServices = $definerServices;
        $this->migrationServices = $migrationServices;
        $this->postProcessorServices = $postProcessorServices;
    }

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

        $complete = true;
        $priority = $this->callLegacyProviders([], $complete);

        foreach ($this->providerServices as $class => $provider) {
            $priority[$class] = $provider->getPriority();
            if (!array_key_exists($class, $this->providerConfig)) {
                if ($provider->isAvailable()) {
                    $this->providerConfig[$class] = $provider->getConfig();
                } else {
                    $complete = false;
                }
            }
        }

        $config = $this->processConfig($priority, $complete);

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
        $complete = true;
        $priority = $this->callLegacyProviders([], $complete);

        foreach ($this->providerServices as $class => $provider) {
            if ($provider instanceof ContextualProvider) {
                continue;
            }
            if (!array_key_exists($class, $this->providerConfig) && $provider->isAvailable()) {
                $this->providerConfig[$class] = $provider->getConfig();
            }
            $priority[$class] = $provider->getPriority();
        }

        return $this->processConfig($priority, false);
    }

    /**
     * Applies the configuration of each provider in order of priority.
     *
     * @return array|array[]|bool[]|int[]|string[] Sorted, merged and type cast configuration.
     * @throws JsonException
     */
    protected function processConfig(array $priority, bool $complete): array
    {
        asort($priority);

        if (!$complete) {
            $processedConfigKey = sha1(json_encode($priority, JSON_THROW_ON_ERROR));
            if (array_key_exists($processedConfigKey, $this->incompleteConfig)) {
                return $this->incompleteConfig[$processedConfigKey];
            }
        }

        $config = [];
        foreach (array_keys($priority) as $class) {
            $config = ConfigurationUtility::mergeConfiguration($config, $this->providerConfig[$class] ?? []);
        }

        $config = $this->postProcessConfig($config);
        $config = $this->migrateConfig($config);
        $config = $this->castConfig($config);

        // Remove previous caches or all if complete
        $this->incompleteConfig = [];
        if (!$complete) {
            $this->incompleteConfig[$processedConfigKey] = $config;
        }

        return $config;
    }

    protected function postProcessConfig(array $config): array
    {
        $config = $this->processLegacyPostProcessors($config);
        foreach ($this->postProcessorServices as $postProcessor) {
            $config = $postProcessor->process($config);
        }
        return $config;
    }

    protected function migrateConfig(array $config): array
    {
        $config = $this->processLegacyMigrations($config);
        foreach ($this->migrationServices as $migration) {
            $config = $migration->migrate($config);
        }
        return $config;
    }

    protected function castConfig(array $config): array
    {
        if ($this->contextService->isLocal()) {
            $config = $this->getLocalDefinition()->cast($config);
        } else {
            $config = $this->getForeignDefinition()->cast($config);
        }
        return $config;
    }

    public function getLocalDefinition(string $path = ''): Node
    {
        if (null === $this->definition['local']) {
            $definition = GeneralUtility::makeInstance(NodeCollection::class);
            $this->addLocalDefinitionFromLegacyDefiners($definition);
            foreach ($this->definerServices as $definer) {
                if ($definer instanceof ConditionalConfigServiceInterface && !$definer->isEnabled()) {
                    continue;
                }
                $definition->addNodes($definer->getLocalDefinition());
            }
            $this->definition['local'] = $definition;
        }
        return $this->definition['local']->getNodePath($path);
    }

    public function getForeignDefinition(string $path = ''): Node
    {
        if (null === $this->definition['foreign']) {
            $definition = GeneralUtility::makeInstance(NodeCollection::class);
            $this->addForeignDefinitionFromLegacyDefiners($definition);
            foreach ($this->definerServices as $definer) {
                $definition->addNodes($definer->getForeignDefinition());
            }
            $this->definition['foreign'] = $definition;
        }
        return $this->definition['foreign']->getNodePath($path);
    }

    public function getMigrationMessages(): array
    {
        $messages = [];
        $messages = $this->addMessagesFromLegacyMigrations($messages);
        foreach ($this->migrationServices as $migrationService) {
            $messages[] = $migrationService->getMessages();
        }
        return array_merge([], ...$messages);
    }

    /**
     * @return array{
     *     providerServices: array<class-string<ProviderServiceInterface>, ProviderServiceInterface>,
     *     legacyProviders: array<class-string<ProviderInterface>, ProviderInterface|null|false>,
     *     definerServices: array<class-string<DefinerServiceInterface>, DefinerServiceInterface>,
     *     legacyDefiners: array<class-string<DefinerInterface>, DefinerInterface|null|false>,
     *     migrationServices: array<class-string<MigrationServiceInterface>, MigrationServiceInterface>,
     *     legacyMigrations: array<class-string<MigrationInterface>, MigrationInterface|null>,
     *     postProcessorServices: array<class-string<PostProcessorServiceInterface>, PostProcessorServiceInterface>,
     *     legacyPostProcessors: array<class-string<PostProcessorInterface>, PostProcessorInterface|null>,
     * }
     * @internal Use the ConfigContainerDumper to dump the ConfigContainer
     */
    public function dumpRaw(): array
    {
        // Trigger the config so all legacy objects are created
        $this->getConfig();
        return [
            'providerServices' => $this->providerServices,
            'legacyProviders' => $this->legacyProviders,
            'definerServices' => $this->definerServices,
            'legacyDefiners' => $this->legacyDefiners,
            'migrationServices' => $this->migrationServices,
            'legacyMigrations' => $this->legacyMigrations,
            'postProcessorServices' => $this->postProcessorServices,
            'legacyPostProcessors' => $this->legacyPostProcessors,
        ];
    }
}
