<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests;

use In2code\In2publishCore\Component\ConfigContainer\ConfigContainer;
use In2code\In2publishCore\Component\ConfigContainer\Definer\In2publishCoreDefiner;
use In2code\In2publishCore\Component\ConfigContainer\Provider\DefaultProvider;
use In2code\In2publishCore\Component\ConfigContainer\Provider\ProviderInterface;
use In2code\In2publishCore\Service\Context\ContextService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function get_class;

class UnitTestCase extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    protected $resetSingletonInstances = true;

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
        $configContainer = new ConfigContainer($contextService);
        $configContainer->registerDefiner(In2publishCoreDefiner::class);
        $configContainer->registerProvider(DefaultProvider::class);
        $configContainer->registerProvider(get_class($testConfigProvider));
        GeneralUtility::setSingletonInstance(ConfigContainer::class, $configContainer);
    }
}
