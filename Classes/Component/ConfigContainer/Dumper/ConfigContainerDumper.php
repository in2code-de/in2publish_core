<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\ConfigContainer\Dumper;

use In2code\In2publishCore\Component\ConfigContainer\ConfigContainer;
use In2code\In2publishCore\Component\ConfigContainer\Definer\DefinerInterface;
use In2code\In2publishCore\Component\ConfigContainer\Definer\DefinerServiceInterface;
use In2code\In2publishCore\Component\ConfigContainer\Factory\ConfigContainerFactory;
use In2code\In2publishCore\Component\ConfigContainer\Migration\MigrationInterface;
use In2code\In2publishCore\Component\ConfigContainer\Migration\MigrationServiceInterface;
use In2code\In2publishCore\Component\ConfigContainer\PostProcessor\PostProcessorInterface;
use In2code\In2publishCore\Component\ConfigContainer\PostProcessor\PostProcessorServiceInterface;
use In2code\In2publishCore\Component\ConfigContainer\Provider\ProviderInterface;
use In2code\In2publishCore\Component\ConfigContainer\Provider\ProviderServiceInterface;

use function array_keys;

class ConfigContainerDumper
{
    private ConfigContainerFactory $configContainerFactory;

    public function __construct(ConfigContainerFactory $configContainerFactory)
    {
        $this->configContainerFactory = $configContainerFactory;
    }

    /**
     * @noinspection PhpUnnecessaryLocalVariableInspection
     */
    public function dump(ConfigContainer $configContainer): array
    {
        $rawDump = $configContainer->dumpRaw();

        $removedServices = $this->configContainerFactory->getRemovedServices();

        $rawDump = $this->dumpProvider($rawDump);
        $rawDump = $this->addRemovedService($rawDump, $removedServices, 'provider', 'providerServices');
        $rawDump = $this->dumpLegacyProvider($rawDump);

        $rawDump = $this->dumpDefiner($rawDump);
        $rawDump = $this->addRemovedService($rawDump, $removedServices, 'definer', 'definerServices');
        $rawDump = $this->dumpLegacyDefiners($rawDump);

        $rawDump = $this->dumpMigrations($rawDump);
        $rawDump = $this->addRemovedService($rawDump, $removedServices, 'migration', 'migrationServices');
        $rawDump = $this->dumpLegacyMigrations($rawDump);

        $rawDump = $this->dumpPostProcessors($rawDump);
        $rawDump = $this->addRemovedService($rawDump, $removedServices, 'postProcessor', 'postProcessorServices');
        $rawDump = $this->dumpLegacyPostProcessors($rawDump);

        return $rawDump;
    }

    private function addRemovedService(
        array $rawDump,
        array $removedServices,
        string $removedServiceKey,
        string $rawDumpKey
    ): array {
        foreach (array_keys($removedServices[$removedServiceKey] ?? []) as $removedServiceClass) {
            $rawDump[$rawDumpKey][$removedServiceClass] = false;
        }
        return $rawDump;
    }

    /**
     * @param array{
     *     providerServices: array<class-string<ProviderServiceInterface>, ProviderServiceInterface>
     * } $rawDump
     */
    private function dumpProvider(array $rawDump): array
    {
        $providerServiceConfig = [];
        foreach ($rawDump['providerServices'] as $class => $providerService) {
            $providerServiceConfig[$class] = [
                'config' => $providerService->getConfig(),
                'priority' => $providerService->getPriority(),
            ];
            $rawDump['providerServices'][$class] = true;
        }
        $rawDump['providerServiceConfig'] = $providerServiceConfig;
        return $rawDump;
    }

    /**
     * @param array{
     *     legacyProviders: array<class-string<ProviderInterface>, ProviderInterface|null|false>
     * } $rawDump
     */
    private function dumpLegacyProvider(array $rawDump): array
    {
        $legacyProviders = [];
        foreach ($rawDump['legacyProviders'] as $class => $legacyProvider) {
            if ($legacyProvider instanceof ProviderInterface) {
                $legacyProviders[$class] = [
                    'config' => $legacyProvider->getConfig(),
                    'priority' => $legacyProvider->getPriority(),
                ];
                $rawDump['legacyProviders'][$class] = true;
            }
        }
        $rawDump['legacyProviderConfig'] = $legacyProviders;
        return $rawDump;
    }

    /**
     * @param array{
     *     definerServices: array<class-string<DefinerServiceInterface>, DefinerServiceInterface>
     * } $rawDump
     */
    private function dumpDefiner(array $rawDump): array
    {
        foreach (array_keys($rawDump['definerServices']) as $class) {
            $rawDump['definerServices'][$class] = true;
        }
        return $rawDump;
    }

    /**
     * @param array{
     *     legacyDefiners: array<class-string<DefinerInterface>, DefinerInterface|null|false>
     * } $rawDump
     */
    private function dumpLegacyDefiners(array $rawDump): array
    {
        foreach ($rawDump['legacyDefiners'] as $class => $legacyDefiner) {
            if ($legacyDefiner instanceof DefinerInterface) {
                $rawDump['legacyDefiners'][$class] = true;
            }
        }
        return $rawDump;
    }

    /**
     * @param array{
     *     migrationServices: array<class-string<MigrationServiceInterface>, MigrationServiceInterface>
     * } $rawDump
     */
    private function dumpMigrations(array $rawDump): array
    {
        $migrationServiceMessages = [];
        foreach ($rawDump['migrationServices'] as $class => $migrationService) {
            $migrationServiceMessages[$class] = $migrationService->getMessages();
            $rawDump['migrationServices'][$class] = true;
        }
        $rawDump['migrationServiceMessages'] = $migrationServiceMessages;
        return $rawDump;
    }

    /**
     * @param array{
     *     legacyMigrations: array<class-string<MigrationInterface>, MigrationInterface|null>
     * } $rawDump
     */
    private function dumpLegacyMigrations(array $rawDump): array
    {
        $legacyMigrationMessages = [];
        foreach ($rawDump['legacyMigrations'] as $class => $legacyMigration) {
            $legacyMigrationMessages[$class] = $legacyMigration;
            if ($legacyMigration instanceof MigrationInterface) {
                $legacyMigrationMessages[$class] = $legacyMigration->getMessages();
            }
        }
        $rawDump['legacyMigrationMessages'] = $legacyMigrationMessages;
        return $rawDump;
    }

    /**
     * @param array{
     *     postProcessorServices: array<class-string<PostProcessorServiceInterface>, PostProcessorServiceInterface>
     * } $rawDump
     */
    private function dumpPostProcessors(array $rawDump): array
    {
        foreach (array_keys($rawDump['postProcessorServices']) as $class) {
            $rawDump['postProcessorServices'][$class] = true;
        }
        return $rawDump;
    }

    /**
     * @param array{
     *     legacyPostProcessors: array<class-string<PostProcessorInterface>, PostProcessorInterface|null>
     * } $rawDump
     */
    private function dumpLegacyPostProcessors(array $rawDump): array
    {
        foreach ($rawDump['legacyPostProcessors'] as $class => $legacyPostProcessor) {
            $rawDump['legacyPostProcessors'][$class] = $legacyPostProcessor instanceof PostProcessorInterface;
        }
        return $rawDump;
    }
}
