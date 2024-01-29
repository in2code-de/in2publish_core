<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\ConfigContainer;

use In2code\In2publishCore\Component\ConfigContainer\Definer\DefinerInterface;
use In2code\In2publishCore\Component\ConfigContainer\Migration\MigrationInterface;
use In2code\In2publishCore\Component\ConfigContainer\Node\NodeCollection;
use In2code\In2publishCore\Component\ConfigContainer\PostProcessor\PostProcessorInterface;
use In2code\In2publishCore\Component\ConfigContainer\Provider\ProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_filter;
use function array_key_exists;
use function sprintf;

use const E_USER_DEPRECATED;

trait DeprecatedConfigContainer
{
    /** @var array<class-string<ProviderInterface>, ProviderInterface|false|null> */
    protected array $legacyProviders = [];
    /** @var array<class-string<DefinerInterface>, DefinerInterface|false|null> */
    protected array $legacyDefiners = [];
    /** @var array<class-string<PostProcessorInterface>, PostProcessorInterface|null> */
    protected array $legacyPostProcessors = [];
    /** @var array<class-string<MigrationInterface>, MigrationInterface|null> */
    protected array $legacyMigrations = [];

    protected function initializeLegacyProviderObjects(): void
    {
        foreach ($this->legacyProviders as $class => $object) {
            if (null === $object) {
                $provider = GeneralUtility::makeInstance($class);
                // If the Provider does not implement the ProviderInterface, it will be skipped (set the default)
                $this->legacyProviders[$class] = false;
                if ($provider instanceof ProviderInterface) {
                    $this->legacyProviders[$class] = $provider;
                    if ($provider instanceof ConditionalConfigServiceInterface && !$provider->isEnabled()) {
                        // Preset the provider's config to never ask it again
                        $this->providerConfig[$class] = null;
                    }
                }
            }
        }
    }

    protected function initializeLegacyDefinerObjects(): void
    {
        foreach ($this->legacyDefiners as $class => $definer) {
            if (null === $definer) {
                $definer = GeneralUtility::makeInstance($class);
                $this->legacyDefiners[$class] = false;
                if ($definer instanceof DefinerInterface) {
                    if ($definer instanceof ConditionalConfigServiceInterface && !$definer->isEnabled()) {
                        continue;
                    }
                    $this->legacyDefiners[$class] = $definer;
                }
            }
        }
    }

    protected function initializeLegacyPostProcessorObjects(): void
    {
        foreach ($this->legacyPostProcessors as $class => $postProcessor) {
            if (null === $postProcessor) {
                $postProcessor = GeneralUtility::makeInstance($class);
                $this->legacyPostProcessors[$class] = false;
                if ($postProcessor instanceof PostProcessorInterface) {
                    if ($postProcessor instanceof ConditionalConfigServiceInterface && !$postProcessor->isEnabled()) {
                        continue;
                    }
                    $this->legacyPostProcessors[$class] = $postProcessor;
                }
            }
        }
    }

    protected function initializeLegacyMigrationObjects(): void
    {
        foreach ($this->legacyMigrations as $class => $migration) {
            if (null === $migration) {
                $migration = GeneralUtility::makeInstance($class);
                $this->legacyMigrations[$class] = false;
                if ($migration instanceof MigrationInterface) {
                    if ($migration instanceof ConditionalConfigServiceInterface && !$migration->isEnabled()) {
                        continue;
                    }
                    $this->legacyMigrations[$class] = $migration;
                }
            }
        }
    }

    protected function addLocalDefinitionFromLegacyDefiners(NodeCollection $nodeCollection): void
    {
        $this->initializeLegacyDefinerObjects();
        foreach (array_filter($this->legacyDefiners) as $definer) {
            $nodeCollection->addNodes($definer->getLocalDefinition());
        }
    }

    protected function addForeignDefinitionFromLegacyDefiners(NodeCollection $nodeCollection): void
    {
        $this->initializeLegacyDefinerObjects();
        foreach (array_filter($this->legacyDefiners) as $definer) {
            $nodeCollection->addNodes($definer->getForeignDefinition());
        }
    }

    protected function addMessagesFromLegacyMigrations(array $messages): array
    {
        $this->initializeLegacyMigrationObjects();
        foreach (array_filter($this->legacyMigrations) as $migration) {
            $messages[] = $migration->getMessages();
        }
        return $messages;
    }

    protected function processLegacyPostProcessors(array $config): array
    {
        $this->initializeLegacyPostProcessorObjects();
        foreach (array_filter($this->legacyPostProcessors) as $postProcessor) {
            $config = $postProcessor->process($config);
        }
        return $config;
    }

    protected function processLegacyMigrations(array $config): array
    {
        $this->initializeLegacyMigrationObjects();
        foreach (array_filter($this->legacyMigrations) as $migration) {
            $config = $migration->migrate($config);
        }
        return $config;
    }

    protected function callLegacyProviders(array $priority, bool &$complete): array
    {
        $this->initializeLegacyProviderObjects();
        foreach (array_filter($this->legacyProviders) as $class => $provider) {
            $priority[$class] = $provider->getPriority();
            if (!array_key_exists($class, $this->providerConfig)) {
                if ($provider->isAvailable()) {
                    $this->providerConfig[$class] = $provider->getConfig();
                } else {
                    $complete = false;
                }
            }
        }
        return $priority;
    }

    /**
     * Providers registered in ext_tables.php will not overrule configurations of already loaded extensions.
     * Providers must implement the ProviderInterface, or they won't be called.
     *
     * @deprecated Implement the ProviderServiceInterface in your provider instead. If your provider is not always
     *     available, you can use the ConditionalConfigServiceInterface to disable the provider. This method will be
     *     removed in in2publish_core v13.
     * @noinspection PhpUnused
     */
    public function registerProvider(string $provider): void
    {
        trigger_error(
            sprintf(
                'Calling ConfigContainer->registerProvider is deprecated. This method will be removed in in2publish_core v13. Implement the ProviderServiceInterface in %s instead.',
                $provider,
            ),
            E_USER_DEPRECATED,
        );
        $this->legacyProviders[$provider] = null;
    }

    /**
     * Definers must implement the DefinerInterface, or they won't be called.
     *
     * @deprecated Implement the DefinerServiceInterface in your provider instead. If your definer is not always
     *     available, you can use the ConditionalConfigServiceInterface to disable the definer. This method will be
     *     removed in in2publish_core v13.
     * @noinspection PhpUnused
     */
    public function registerDefiner(string $definer): void
    {
        trigger_error(
            sprintf(
                'Calling ConfigContainer->registerDefiner is deprecated. This method will be removed in in2publish_core v13. Implement the DefinerServiceInterface in %s instead.',
                $definer,
            ),
            E_USER_DEPRECATED,
        );
        $this->legacyDefiners[$definer] = null;
    }

    /**
     * PostProcessors must implement the PostProcessorInterface, or they won't be called.
     *
     * @deprecated Implement the ProviderServiceInterface in your provider instead. If your post processor is not
     *     always available, you can use the ConditionalConfigServiceInterface to disable the post processor. This
     *     method will be removed in in2publish_core v13.
     * @noinspection PhpUnused
     */
    public function registerPostProcessor(string $postProcessor): void
    {
        trigger_error(
            sprintf(
                'Calling ConfigContainer->registerPostProcessor is deprecated. This method will be removed in in2publish_core v13. Implement the PostProcessorServiceInterface in %s instead.',
                $postProcessor,
            ),
            E_USER_DEPRECATED,
        );
        $this->legacyPostProcessors[$postProcessor] = null;
    }

    /**
     * All migrations must be registered in ext_localconf.php!
     * Migrations must implement the MigrationInterface.
     *
     * @deprecated Implement the ProviderServiceInterface in your provider instead. If your migration is not always
     *     available, you can use the ConditionalConfigServiceInterface to disable the migration. This method will be
     *     removed in in2publish_core v13.
     * @noinspection PhpUnused
     */
    public function registerMigration(string $migration): void
    {
        trigger_error(
            sprintf(
                'Calling ConfigContainer->registerMigration is deprecated. This method will be removed in in2publish_core v13. Implement the MigrationServiceInterface in %s instead.',
                $migration,
            ),
            E_USER_DEPRECATED,
        );
        $this->legacyMigrations[$migration] = null;
    }
}
