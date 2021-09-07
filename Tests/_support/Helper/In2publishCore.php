<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Helper;

use Codeception\Module;
use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Config\Definer\In2publishCoreDefiner;
use In2code\In2publishCore\Config\Provider\DefaultProvider;
use In2code\In2publishCore\Config\Provider\ProviderInterface;
use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Utility\DatabaseUtility;
use ReflectionClass;
use ReflectionProperty;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class In2publishCore extends Module
{
    public function setupIn2publishConfig(array $config)
    {
        $testConfigProvider = new class implements ProviderInterface {
            public $config = [];

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
        $testConfigProvider->config = $config;
        $configContainer = new ConfigContainer();
        $configContainer->registerDefiner(In2publishCoreDefiner::class);
        $configContainer->registerProvider(DefaultProvider::class);
        $configContainer->registerProvider(get_class($testConfigProvider));
        GeneralUtility::setSingletonInstance(ConfigContainer::class, $configContainer);
    }

    public function buildForeignDatabaseConnection()
    {
        $file = realpath(__DIR__ . '/../../_data/foreign.db');
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['in2publish_foreign'] = [
            'driver' => 'pdo_sqlite',
            'url' => 'sqlite3:////' . $file,
        ];
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $foreignConnection = $connectionPool->getConnectionByName('in2publish_foreign');
        foreach ($foreignConnection->getEventManager()->getListeners() as $event => $listeners) {
            foreach ($listeners as $listener) {
                $foreignConnection->getEventManager()->removeEventListener($event, $listener);
            }
        }
        $foreignConnection->connect();
        $reflection = new ReflectionClass(DatabaseUtility::class);
        $reflectionProperty = $reflection->getProperty('foreignConnection');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(DatabaseUtility::class, $foreignConnection);
    }

    public function clearIn2publishContext()
    {
        $this->setIn2publishContext(null);
        $this->setRedirectedIn2publishContext(null);
    }

    public function setIn2publishContext($value)
    {
        if (null === $value) {
            putenv(ContextService::ENV_VAR_NAME);
        } else {
            putenv(ContextService::ENV_VAR_NAME . '=' . $value);
        }
    }

    public static function setRedirectedIn2publishContext($value)
    {
        if (null === $value) {
            putenv(ContextService::REDIRECT_ENV_VAR_NAME);
        } else {
            putenv(ContextService::REDIRECT_ENV_VAR_NAME . '=' . $value);
        }
    }

    public function setApplicationContext($value)
    {
        if (null === $value) {
            putenv('TYPO3_CONTEXT');
            $applicationContext = new ApplicationContext('Production');
        } else {
            putenv('TYPO3_CONTEXT=' . $value);
            $applicationContext = new ApplicationContext($value);
        }
        $this->setStaticProperty(GeneralUtility::class, 'applicationContext', $applicationContext);
        $environmentReflection = new ReflectionProperty(Environment::class, 'context');
        $environmentReflection->setAccessible(true);
        $environmentReflection->setValue(Environment::class, new ApplicationContext($value));
    }

    public function setStaticProperty(string $class, string $name, $value)
    {
        $reflectionProperty = new ReflectionProperty($class, $name);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($value);
    }
}
