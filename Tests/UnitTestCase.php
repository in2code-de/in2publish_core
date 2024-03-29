<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests;

use In2code\In2publishCore\Component\ConfigContainer\ConfigContainer;
use In2code\In2publishCore\Component\ConfigContainer\Definer\In2publishCoreDefiner;
use In2code\In2publishCore\Component\ConfigContainer\Provider\DefaultProvider;
use In2code\In2publishCore\Component\ConfigContainer\Provider\ProviderInterface;
use In2code\In2publishCore\Service\Context\ContextService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UnitTestCase extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    public function initializeIn2publishConfig(array $config = [])
    {
        $testConfigProvider = new class implements ProviderInterface {
            public array $config = [];

            public function isAvailable(): bool
            {
                return true;
            }

            public function getConfig(): array
            {
                return $this->config;
            }

            public function getPriority(): int
            {
                return 1000;
            }
        };
        $contextService = GeneralUtility::makeInstance(ContextService::class);
        $testConfigProvider->config = $config;
        $configContainer = new ConfigContainer(
            [new DefaultProvider(), $testConfigProvider],
            [new In2publishCoreDefiner()],
            [],
            [],
        );
        $configContainer->injectContextService($contextService);
        GeneralUtility::setSingletonInstance(ConfigContainer::class, $configContainer);
    }
}
