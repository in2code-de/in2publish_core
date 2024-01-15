<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Exception;
use In2code\In2publishCore\Component\ConfigContainer\ConfigContainer;
use In2code\In2publishCore\Component\ConfigContainer\Definer\In2publishCoreDefiner;
use In2code\In2publishCore\Component\ConfigContainer\Provider\DefaultProvider;
use In2code\In2publishCore\Component\ConfigContainer\Provider\ProviderInterface;
use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Utility\DatabaseUtility;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Snapshot\DatabaseSnapshot;
use TYPO3\TestingFramework\Core\Testbase;

use function copy;
use function defined;
use function dirname;
use function in_array;
use function putenv;

/**
 * @SuppressWarnings(PHPMD)
 */
abstract class FunctionalTestCase extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extensionmanager',
        'typo3conf/ext/in2publish_core',
    ];
    private ContainerInterface $container;
    /**
     * These two internal variable track if the given test is the first test of
     * that test case. This variable is set to current calling test case class.
     * Consecutive tests then optimize and do not create a full
     * database structure again but instead just truncate all tables which
     * is much quicker.
     */
    private static string $currentTestCaseClass = '';
    private bool $isFirstTest = true;

    /**
     * Set up creates a test instance and database.
     *
     * This method should be called with parent::setUp() in your test cases!
     */
    protected function setUp(): void
    {
        if (!defined('ORIGINAL_ROOT')) {
            self::markTestSkipped('Functional tests must be called through phpunit on CLI');
        }

        $this->identifier = self::getInstanceIdentifier();
        $this->instancePath = self::getInstancePath();
        putenv('TYPO3_PATH_ROOT=' . $this->instancePath);
        putenv('TYPO3_PATH_APP=' . $this->instancePath);

        $testbase = new Testbase();
        $testbase->setTypo3TestingContext();

        // See if we're the first test of this test case.
        $currentTestCaseClass = static::class;
        if (self::$currentTestCaseClass !== $currentTestCaseClass) {
            self::$currentTestCaseClass = $currentTestCaseClass;
        } else {
            $this->isFirstTest = false;
        }

        // sqlite db path preparation
        $dbPathSqlite = dirname($this->instancePath) . '/functional-sqlite-dbs/test_' . $this->identifier . '.sqlite';
        $dbPathSqliteEmpty = dirname($this->instancePath) . '/functional-sqlite-dbs/test_' . $this->identifier . '.empty.sqlite';

        if (!$this->isFirstTest) {
            // Reusing an existing instance. This typically happens for the second, third, ... test
            // in a test case, so environment is set up only once per test case.
            GeneralUtility::purgeInstances();
            $this->container = $testbase->setUpBasicTypo3Bootstrap($this->instancePath);
            if ($this->initializeDatabase) {
                $testbase->initializeTestDatabaseAndTruncateTables($dbPathSqlite, $dbPathSqliteEmpty);
            }
            $testbase->loadExtensionTables();
        } else {
            DatabaseSnapshot::initialize(dirname($this->getInstancePath()) . '/functional-sqlite-dbs/', $this->identifier);
            $testbase->removeOldInstanceIfExists($this->instancePath);
            // Basic instance directory structure
            $testbase->createDirectory($this->instancePath . '/fileadmin');
            $testbase->createDirectory($this->instancePath . '/typo3temp/var/transient');
            $testbase->createDirectory($this->instancePath . '/typo3temp/assets');
            $testbase->createDirectory($this->instancePath . '/typo3conf/ext');
            // Additionally requested directories
            foreach ($this->additionalFoldersToCreate as $directory) {
                $testbase->createDirectory($this->instancePath . '/' . $directory);
            }
            $defaultCoreExtensionsToLoad = [
                'core',
                'backend',
                'frontend',
                'extbase',
                'install',
                'fluid',
            ];
            $frameworkExtension = [
                'Resources/Core/Functional/Extensions/json_response',
                'Resources/Core/Functional/Extensions/private_container',
            ];
            $testbase->setUpInstanceCoreLinks($this->instancePath, $defaultCoreExtensionsToLoad, $this->coreExtensionsToLoad);
            $testbase->linkTestExtensionsToInstance($this->instancePath, $this->testExtensionsToLoad);
            $testbase->linkFrameworkExtensionsToInstance($this->instancePath, $frameworkExtension);
            $testbase->linkPathsInTestInstance($this->instancePath, $this->pathsToLinkInTestInstance);
            $testbase->providePathsInTestInstance($this->instancePath, $this->pathsToProvideInTestInstance);

            $localConfiguration = ['DB' => ['Connections' => []]];
            $connections = &$localConfiguration['DB']['Connections'];
            $connections['Default']['dbname'] = 'local';
            $connections['Default']['host'] = 'mysql';
            $connections['Default']['user'] = 'root';
            $connections['Default']['password'] = 'root';
            $connections['Default']['port'] = 3306;
            $connections['Default']['unix_socket'] = null;
            $connections['Default']['driver'] = 'mysqli';
            $connections['Default']['charset'] = 'utf8mb4';

            $connections['Foreign']['dbname'] = 'foreign';
            $connections['Foreign']['host'] = 'mysql';
            $connections['Foreign']['user'] = 'root';
            $connections['Foreign']['password'] = 'root';
            $connections['Foreign']['port'] = 3306;
            $connections['Foreign']['unix_socket'] = null;
            $connections['Foreign']['driver'] = 'mysqli';
            $connections['Foreign']['charset'] = 'utf8mb4';

            $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'] = $connections;

            $originalDatabaseName = '';
            $dbName = '';
            $dbDriver = $localConfiguration['DB']['Connections']['Default']['driver'];
            if ($dbDriver !== 'pdo_sqlite') {
                $originalDatabaseName = $localConfiguration['DB']['Connections']['Default']['dbname'];
                if ($originalDatabaseName !== preg_replace('/[^a-zA-Z0-9_]/', '', $originalDatabaseName)) {
                    throw new \RuntimeException(
                        sprintf(
                            'Database name "%s" is invalid. Use a valid name, for example "%s".',
                            $originalDatabaseName,
                            preg_replace('/[^a-zA-Z0-9_]/', '', $originalDatabaseName)
                        ),
                        1695139917
                    );
                }
                // Append the unique identifier to the base database name to end up with a single database per test case
                $dbName = $originalDatabaseName . '_ft' . $this->identifier;
                $localConfiguration['DB']['Connections']['Default']['dbname'] = $dbName;
                $testbase->testDatabaseNameIsNotTooLong($originalDatabaseName, $localConfiguration);
                if ($dbDriver === 'mysqli' || $dbDriver === 'pdo_mysql') {
                    $localConfiguration['DB']['Connections']['Default']['charset'] = 'utf8mb4';
                    $localConfiguration['DB']['Connections']['Default']['tableoptions']['charset'] = 'utf8mb4';
                    $localConfiguration['DB']['Connections']['Default']['tableoptions']['collate'] = 'utf8mb4_unicode_ci';
                    $localConfiguration['DB']['Connections']['Default']['initCommands'] = 'SET SESSION sql_mode = \'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_VALUE_ON_ZERO,NO_ENGINE_SUBSTITUTION,NO_ZERO_DATE,NO_ZERO_IN_DATE,ONLY_FULL_GROUP_BY\';';
                }

                $originalForeignDatabaseName = $localConfiguration['DB']['Connections']['Foreign']['dbname'];
                $foreignDbName = $originalForeignDatabaseName . '_ft' . $this->identifier;
                $localConfiguration['DB']['Connections']['Foreign']['dbname'] = $foreignDbName;
                $testbase->testDatabaseNameIsNotTooLong($originalForeignDatabaseName, $localConfiguration);
                if ($dbDriver === 'mysqli') {
                    $localConfiguration['DB']['Connections']['Default']['initCommands'] = 'SET SESSION sql_mode = \'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_VALUE_ON_ZERO,NO_ENGINE_SUBSTITUTION,NO_ZERO_DATE,NO_ZERO_IN_DATE,ONLY_FULL_GROUP_BY\';';
                    $localConfiguration['DB']['Connections']['Foreign']['initCommands'] = 'SET SESSION sql_mode = \'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_VALUE_ON_ZERO,NO_ENGINE_SUBSTITUTION,NO_ZERO_DATE,NO_ZERO_IN_DATE,ONLY_FULL_GROUP_BY\';';
                }
            } else {
                // sqlite dbs of all tests are stored in a dir parallel to instance roots. Allows defining this path as tmpfs.
                $testbase->createDirectory(dirname($this->instancePath) . '/functional-sqlite-dbs');
                $localConfiguration['DB']['Connections']['Default']['path'] = $dbPathSqlite;
            }

            // Set some hard coded base settings for the instance. Those could be overruled by
            // $this->configurationToUseInTestInstance if needed again.
            $localConfiguration['SYS']['displayErrors'] = '1';
            $localConfiguration['SYS']['debugExceptionHandler'] = '';
            // By setting errorHandler to empty string, only the phpunit error handler is
            // registered in functional tests, so settings like convertWarningsToExceptions="true"
            // in FunctionalTests.xml will let tests fail that throw warnings.
            $localConfiguration['SYS']['errorHandler'] = '';
            $localConfiguration['SYS']['trustedHostsPattern'] = '.*';
            $localConfiguration['SYS']['encryptionKey'] = 'i-am-not-a-secure-encryption-key';
            $localConfiguration['GFX']['processor'] = 'GraphicsMagick';
            // Set cache backends to null backend instead of database backend let us save time for creating
            // database schema for it and reduces selects/inserts to the database for cache operations, which
            // are generally not really needed for functional tests. Specific tests may restore this in if needed.
            $localConfiguration['SYS']['caching']['cacheConfigurations']['hash']['backend'] = 'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend';
            $localConfiguration['SYS']['caching']['cacheConfigurations']['imagesizes']['backend'] = 'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend';
            $localConfiguration['SYS']['caching']['cacheConfigurations']['pages']['backend'] = 'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend';
            $localConfiguration['SYS']['caching']['cacheConfigurations']['rootline']['backend'] = 'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend';
            $testbase->setUpLocalConfiguration($this->instancePath, $localConfiguration, $this->configurationToUseInTestInstance);
            $testbase->setUpPackageStates(
                $this->instancePath,
                $defaultCoreExtensionsToLoad,
                $this->coreExtensionsToLoad,
                $this->testExtensionsToLoad,
                $frameworkExtension
            );
            $this->container = $testbase->setUpBasicTypo3Bootstrap($this->instancePath);
            if ($this->initializeDatabase) {
                if ($dbDriver !== 'pdo_sqlite') {
                    $testbase->setUpTestDatabase($dbName, $originalDatabaseName);
                    $testbase->setUpTestDatabase($foreignDbName, $originalForeignDatabaseName);
                } else {
                    $testbase->setUpTestDatabase($dbPathSqlite, $originalDatabaseName);
                    $testbase->setUpTestDatabase($dbPathSqlite, $originalForeignDatabaseName);
                }
            }
            if ($this->initializeDatabase) {
                $foreignBackup = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Foreign'];
                $defaultBackup = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'];
                unset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Foreign']);
                $testbase->createDatabaseStructure($this->container);
                $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'] = $foreignBackup;
                $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
                $connectionPool->resetConnections();
                $testbase->createDatabaseStructure($this->container);
                $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'] = $foreignBackup;
                $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Foreign'] = $defaultBackup;
                $connectionPool->resetConnections();
                $foreignConnection = $connectionPool->getConnectionByName('Foreign');

                $reflection = new ReflectionProperty(DatabaseUtility::class, 'foreignConnection');
                $reflection->setAccessible(true);
                $reflection->setValue(DatabaseUtility::class, $foreignConnection);

                if ($dbDriver === 'pdo_sqlite') {
                    // Copy sqlite file '/path/functional-sqlite-dbs/test_123.sqlite' to
                    // '/path/functional-sqlite-dbs/test_123.empty.sqlite'. This is re-used for consecutive tests.
                    copy($dbPathSqlite, $dbPathSqliteEmpty);
                }
            }
            $testbase->loadExtensionTables();
        }
        $this->initializeIn2publishConfig();

        $backendUserAuthentication = new BackendUserAuthentication();
        $backendUserAuthentication->user = [
            'uid' => 1,
            'admin' => 1,
        ];
        $GLOBALS['BE_USER'] = $backendUserAuthentication;
    }

    /**
     * Create a low level connection to dbms, without selecting the target database.
     * Drop existing database if it exists and create a new one.
     *
     * @param string $databaseName Database name of this test instance
     * @param string $originalDatabaseName Original database name before suffix was added
     * @return void
     * @throws \TYPO3\TestingFramework\Core\Exception
     */
    private function setUpTestDatabase(string $databaseName, string $originalDatabaseName): void
    {
        // First close existing connections from a possible previous test case and
        // tell our ConnectionPool there are no current connections anymore.
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        $connection->close();
        $connectionPool->resetConnections();

        // Drop database if exists. Directly using the Doctrine DriverManager to
        // work around connection caching in ConnectionPool.
        $connectionParameters = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Foreign'];
        unset($connectionParameters['dbname']);
        $schemaManager = DriverManager::getConnection($connectionParameters)->getSchemaManager();

        if ($schemaManager->getDatabasePlatform()->getName() === 'sqlite') {
            // This is the "path" option in sqlite: one file = one db
            $schemaManager->dropDatabase($databaseName);
        } elseif (in_array($databaseName, $schemaManager->listDatabases(), true)) {
            // Suppress listDatabases() call on sqlite which is not implemented there, but
            // check db existence on all other platforms before drop call
            $schemaManager->dropDatabase($databaseName);
        }
        try {
            $schemaManager->createDatabase($databaseName);
        } catch (DBALException $e) {
            $user = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Foreign']['user'];
            $host = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Foreign']['host'];
            throw new Exception(
                'Unable to create database with name ' . $databaseName . '. This is probably a permission problem.'
                . ' For this instance this could be fixed executing:'
                . ' GRANT ALL ON `' . $originalDatabaseName . '_%`.* TO `' . $user . '`@`' . $host . '`;'
                . ' Original message thrown by database layer: ' . $e->getMessage(),
                1376579070,
            );
        }
    }

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
