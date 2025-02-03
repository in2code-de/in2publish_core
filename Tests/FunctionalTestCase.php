<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests;

use CoStack\ProcessManager\ProcessManager;
use Doctrine\DBAL\DBALException;
use Exception;
use Psr\Container\ContainerInterface;
use Symfony\Component\Process\Process;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Testbase;

use function defined;
use function ob_end_clean;
use function register_shutdown_function;
use function spl_autoload_functions;

use const ORIGINAL_ROOT;

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

        $testbase = new Testbase();
        $testbase->setTypo3TestingContext();

        // See if we're the first test of this test case.
        $currentTestCaseClass = static::class;
        if (self::$currentTestCaseClass !== $currentTestCaseClass) {
            self::$currentTestCaseClass = $currentTestCaseClass;
        } else {
            $this->isFirstTest = false;
        }

        if (!$this->isFirstTest) {
            // Reusing an existing instance. This typically happens for the second, third, ... test
            // in a test case, so environment is set up only once per test case.
            GeneralUtility::purgeInstances();
        } else {
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
            $connections['Default']['tableoptions']['charset'] = 'utf8mb4';
            $connections['Default']['tableoptions']['collate'] = 'utf8mb4_unicode_ci';
            $connections['Default']['initCommands'] = 'SET SESSION sql_mode = \'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_VALUE_ON_ZERO,NO_ENGINE_SUBSTITUTION,NO_ZERO_DATE,NO_ZERO_IN_DATE,ONLY_FULL_GROUP_BY\';';

            $connections['Foreign']['dbname'] = 'foreign';
            $connections['Foreign']['host'] = 'mysql';
            $connections['Foreign']['user'] = 'root';
            $connections['Foreign']['password'] = 'root';
            $connections['Foreign']['port'] = 3306;
            $connections['Foreign']['unix_socket'] = null;
            $connections['Foreign']['driver'] = 'mysqli';
            $connections['Foreign']['charset'] = 'utf8mb4';
            $connections['Foreign']['tableoptions']['charset'] = 'utf8mb4';
            $connections['Foreign']['tableoptions']['collate'] = 'utf8mb4_unicode_ci';
            $connections['Foreign']['initCommands'] = 'SET SESSION sql_mode = \'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_VALUE_ON_ZERO,NO_ENGINE_SUBSTITUTION,NO_ZERO_DATE,NO_ZERO_IN_DATE,ONLY_FULL_GROUP_BY\';';

            $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'] = $connections;
        }
        $_SERVER['PWD'] = ORIGINAL_ROOT;
        $_SERVER['argv'][0] = 'typo3/index.php';

        // Reset state from a possible previous run
        GeneralUtility::purgeInstances();

        $classLoader = require $testbase->getPackagesPath() . '/autoload.php';
        SystemEnvironmentBuilder::run(1, SystemEnvironmentBuilder::REQUESTTYPE_BE | SystemEnvironmentBuilder::REQUESTTYPE_CLI);
        $this->container = Bootstrap::init($classLoader);
        $languageServiceFactory = GeneralUtility::makeInstance(LanguageServiceFactory::class);
        $GLOBALS['LANG'] = $languageServiceFactory->create('default');
        // Make sure output is not buffered, so command-line output can take place and
        // phpunit does not whine about changed output bufferings in tests.
        ob_end_clean();

        if ($this->initializeDatabase) {
            $this->setupTestEnvironment();
        }
        $testbase->loadExtensionTables();

        $backendUserAuthentication = new BackendUserAuthentication();
        $backendUserAuthentication->user = [
            'uid' => 1,
            'admin' => 1,
        ];
        $GLOBALS['BE_USER'] = $backendUserAuthentication;
    }

    protected function setupTestEnvironment(): void
    {
        $env = [];
        $env['XDEBUG_SESSION'] = 0;
        $env['PHP_IDE_CONFIG'] = 'serverName=local.v13.in2publish-core.de';

        $processes = [];
        $processes[] = new Process(
            [
                'rsync',
                '-a',
                '--delete',
                '--verbose',
                '/app/.project/data/fileadmin/local/',
                '/app/Build/local/public/fileadmin/',
            ],
            env: $env,
        );
        $processes[] = new Process(
            [
                'rsync',
                '-a',
                '--delete',
                '--verbose',
                '/app/.project/data/fileadmin/foreign/',
                '/app/Build/foreign/public/fileadmin/',
            ],
            env: $env,
        );
        $processes[] = new Process(
            [
                '/app/Build/local/vendor/bin/mysql-loader',
                'import',
                '-Hmysql',
                '-uroot',
                '-proot',
                '-Dlocal',
                '-f/.project/data/dumps/local/',
            ],
            env: $env,
        );
        $processes[] = new Process(
            [
                '/app/Build/local/vendor/bin/mysql-loader',
                'import',
                '-Hmysql',
                '-uroot',
                '-proot',
                '-Dforeign',
                '-f/.project/data/dumps/foreign/',
            ],
            env: $env,
        );
        register_shutdown_function(static function () use ($processes) {
            foreach ($processes as $process) {
                /** @var Process $process */
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
        });

        $processManager = new ProcessManager();
        $processManager->runParallel($processes, 4);

        $outputs = [];
        $errors = [];

        foreach ($processes as $process) {
            $outputs[] = $process->getOutput();
            $errors[] = $process->getErrorOutput();
            if (!$process->isSuccessful()) {
                throw new Exception(
                    'Error while setup: ' . $process->getOutput() . "\n" . $process->getErrorOutput(), 7218854885,
                );
            }
        }
    }
}
