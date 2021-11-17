<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests;

use RuntimeException;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Exception;
use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Config\Definer\In2publishCoreDefiner;
use In2code\In2publishCore\Config\Provider\DefaultProvider;
use In2code\In2publishCore\Config\Provider\ProviderInterface;
use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Utility\DatabaseUtility;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\DatabaseConnectionWrapper;
use TYPO3\TestingFramework\Core\Testbase;

use function copy;
use function defined;
use function dirname;
use function get_called_class;
use function get_class;
use function getenv;
use function in_array;
use function putenv;

abstract class FunctionalTestCase extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    protected $testExtensionsToLoad = [
        'typo3/sysext/extensionmanager',
        'typo3conf/ext/in2publish_core',
    ];

    private ContainerInterface $container;

    /**
     * This internal variable tracks if the given test is the first test of
     * that test case. This variable is set to current calling test case class.
     * Consecutive tests then optimize and do not create a full
     * database structure again but instead just truncate all tables which
     * is much quicker.
     *
     * @var string
     */
    private static ?string $currestTestCaseClass = null;

    /**
     * Set up creates a test instance and database.
     *
     * This method should be called with parent::setUp() in your test cases!
     *
     * @return void
     * @throws DBALException
     */
    protected function setUp(): void
    {
        if (!defined('ORIGINAL_ROOT')) {
            $this->markTestSkipped('Functional tests must be called through phpunit on CLI');
        }

        $this->identifier = self::getInstanceIdentifier();
        $this->instancePath = self::getInstancePath();
        putenv('TYPO3_PATH_ROOT=' . $this->instancePath);
        putenv('TYPO3_PATH_APP=' . $this->instancePath);

        $testbase = new Testbase();
        $testbase->defineTypo3ModeBe();
        $testbase->setTypo3TestingContext();

        $isFirstTest = false;
        $currentTestCaseClass = get_called_class();
        if (self::$currestTestCaseClass !== $currentTestCaseClass) {
            $isFirstTest = true;
            self::$currestTestCaseClass = $currentTestCaseClass;
        }

        // sqlite db path preparation
        $dbPathSqlite = dirname($this->instancePath) . '/functional-sqlite-dbs/test_' . $this->identifier . '.sqlite';
        $dbPathSqliteEmpty = dirname($this->instancePath)
                             . '/functional-sqlite-dbs/test_'
                             . $this->identifier
                             . '.empty.sqlite';

        $localConfiguration = ['DB' => ['Connections' => []]];
        $connections = &$localConfiguration['DB']['Connections'];
        $connections['Default']['dbname'] = getenv('localDatabaseName') ?: null;
        $connections['Default']['host'] = getenv('localDatabaseHost') ?: null;
        $connections['Default']['user'] = getenv('localDatabaseUsername') ?: null;
        $connections['Default']['password'] = getenv('localDatabasePassword') ?: null;
        $connections['Default']['port'] = getenv('localDatabasePort') ?: null;
        $connections['Default']['unix_socket'] = getenv('localDatabaseSocket') ?: null;
        $connections['Default']['driver'] = getenv('localDatabaseDriver') ?: 'mysqli';
        $connections['Default']['charset'] = getenv('localDatabaseCharset') ?: null;

        $connections['Foreign']['dbname'] = getenv('foreignDatabaseName') ?: null;
        $connections['Foreign']['host'] = getenv('foreignDatabaseHost') ?: null;
        $connections['Foreign']['user'] = getenv('foreignDatabaseUsername') ?: null;
        $connections['Foreign']['password'] = getenv('foreignDatabasePassword') ?: null;
        $connections['Foreign']['port'] = getenv('foreignDatabasePort') ?: null;
        $connections['Foreign']['unix_socket'] = getenv('foreignDatabaseSocket') ?: null;
        $connections['Foreign']['driver'] = getenv('foreignDatabaseDriver') ?: 'mysqli';
        $connections['Foreign']['charset'] = getenv('foreignDatabaseCharset') ?: null;

        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'] = $connections;

        if (!$isFirstTest) {
            // Reusing an existing instance. This typically happens for the second, third, ... test
            // in a test case, so environment is set up only once per test case.
            GeneralUtility::purgeInstances();
            $this->container = $testbase->setUpBasicTypo3Bootstrap($this->instancePath);
            if ($this->initializeDatabase) {
                $testbase->initializeTestDatabaseAndTruncateTables($dbPathSqlite, $dbPathSqliteEmpty);
            }
            $testbase->loadExtensionTables();
        } else {
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
            $testbase->setUpInstanceCoreLinks($this->instancePath);
            $testbase->linkTestExtensionsToInstance($this->instancePath, $this->testExtensionsToLoad);
            $testbase->linkFrameworkExtensionsToInstance($this->instancePath, $this->frameworkExtensionsToLoad);
            $testbase->linkPathsInTestInstance($this->instancePath, $this->pathsToLinkInTestInstance);
            $testbase->providePathsInTestInstance($this->instancePath, $this->pathsToProvideInTestInstance);

            $originalLocalDatabaseName = '';
            $localDbName = '';
            $dbDriver = $localConfiguration['DB']['Connections']['Default']['driver'];
            if ($dbDriver !== 'pdo_sqlite') {
                $originalLocalDatabaseName = $localConfiguration['DB']['Connections']['Default']['dbname'];
                // Append the unique identifier to the base database name to end up with a single database per test case
                $localDbName = $originalLocalDatabaseName . '_ft' . $this->identifier;
                $localConfiguration['DB']['Connections']['Default']['dbname'] = $localDbName;
                $localConfiguration['DB']['Connections']['Default']['wrapperClass'] = DatabaseConnectionWrapper::class;
                $testbase->testDatabaseNameIsNotTooLong($originalLocalDatabaseName, $localConfiguration);

                $originalLForeignDatabaseName = $localConfiguration['DB']['Connections']['Foreign']['dbname'];
                $foreignDbName = $originalLForeignDatabaseName . '_ft' . $this->identifier;
                $localConfiguration['DB']['Connections']['Foreign']['dbname'] = $foreignDbName;
                $localConfiguration['DB']['Connections']['Foreign']['wrapperClass'] = DatabaseConnectionWrapper::class;
                $testbase->testDatabaseNameIsNotTooLong($originalLForeignDatabaseName, $localConfiguration);
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
            $localConfiguration['SYS']['trustedHostsPattern'] = '.*';
            $localConfiguration['SYS']['encryptionKey'] = 'i-am-not-a-secure-encryption-key';
            $localConfiguration['SYS']['caching']['cacheConfigurations']['extbase_object']['backend'] = NullBackend::class;
            $localConfiguration['GFX']['processor'] = 'GraphicsMagick';
            $testbase->setUpLocalConfiguration(
                $this->instancePath,
                $localConfiguration,
                $this->configurationToUseInTestInstance
            );

            $defaultCoreExtensionsToLoad = [
                'core',
                'backend',
                'frontend',
                'extbase',
                'install',
                'recordlist',
                'fluid',
            ];
            $testbase->setUpPackageStates(
                $this->instancePath,
                $defaultCoreExtensionsToLoad,
                $this->coreExtensionsToLoad,
                $this->testExtensionsToLoad,
                $this->frameworkExtensionsToLoad
            );
            $this->container = $testbase->setUpBasicTypo3Bootstrap($this->instancePath);
            if ($this->initializeDatabase) {
                if ($dbDriver !== 'pdo_sqlite') {
                    $testbase->setUpTestDatabase($localDbName, $originalLocalDatabaseName);
                    $testbase->setUpTestDatabase($foreignDbName, $originalLForeignDatabaseName);
                } else {
                    $testbase->setUpTestDatabase($dbPathSqlite, $originalLocalDatabaseName);
                    $testbase->setUpTestDatabase($dbPathSqlite, $originalLForeignDatabaseName);
                }
            }
            if ($this->initializeDatabase) {
                $foreignBackup = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Foreign'];
                $defaultBackup = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'];
                unset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Foreign']);
                $testbase->createDatabaseStructure();
                $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'] = $foreignBackup;
                $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
                $connectionPool->resetConnections();
                $testbase->createDatabaseStructure();
                $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'] = $foreignBackup;
                $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Foreign'] = $defaultBackup;
                $connectionPool->resetConnections();
                $foreignConnection = $connectionPool->getConnectionByName('Foreign');

                $reflection = new ReflectionProperty(DatabaseUtility::class, 'foreignConnection');
                $reflection->setAccessible(true);
                $reflection->setValue(DatabaseUtility::class, $foreignConnection);

                $testbase->createDatabaseStructure();
                if ($dbDriver === 'pdo_sqlite') {
                    // Copy sqlite file '/path/functional-sqlite-dbs/test_123.sqlite' to
                    // '/path/functional-sqlite-dbs/test_123.empty.sqlite'. This is re-used for consequtive tests.
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

    protected function getContainer(): ContainerInterface
    {
        if (!$this->container instanceof ContainerInterface) {
            throw new RuntimeException('Please invoke parent::setUp() before calling getContainer().', 1589221777);
        }
        return $this->container;
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
                1376579070
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
        $configContainer = new ConfigContainer($contextService);
        $configContainer->registerDefiner(In2publishCoreDefiner::class);
        $configContainer->registerProvider(DefaultProvider::class);
        $configContainer->registerProvider(get_class($testConfigProvider));
        GeneralUtility::setSingletonInstance(ConfigContainer::class, $configContainer);
    }
}
