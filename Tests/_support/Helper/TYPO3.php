<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Tests\Helper;

use Codeception\Module;
use ReflectionClass;
use RuntimeException;
use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Package\FailsafePackageManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\TestingFramework\Core\DatabaseConnectionWrapper;
use TYPO3\TestingFramework\Core\Testbase;

class TYPO3 extends Module
{
    /**
     * If set to true, setUp() will back up the state of the
     * TYPO3\CMS\Core\Core\Environment class and restore it
     * in tearDown().
     *
     * This is needed for tests that reset state of Environment
     * by calling Environment::init() to for instance fake paths
     * or force windows environment.
     *
     * @var bool
     */
    protected $backupEnvironment = false;

    /**
     * If set to true, tearDown() will purge singleton instances created by the test.
     *
     * Unit tests that trigger singleton creation via makeInstance() should set this
     * to true to reset the framework internal singleton state after test execution.
     *
     * A test having this property set to true declares that the system under test
     * includes functionality that does change global framework state. This bit of
     * information is the reason why tearDown() does not reset singletons automatically.
     * tearDown() will make the test fail if that property has not been set to true
     * and if there are remaining singletons after test execution.
     *
     * @var bool
     */
    protected $resetSingletonInstances = true;

    /**
     * Absolute path to files that should be removed after a test.
     * Handled in tearDown. Tests can register here to get any files
     * within typo3temp/ or typo3conf/ext cleaned up again.
     *
     * @var array
     */
    protected $testFilesToDelete = [];

    /**
     * Holds state of TYPO3\CMS\Core\Core\Environment if
     * $this->backupEnvironment has been set to true in a test case
     *
     * @var array
     */
    private $backedUpEnvironment = [];

    /**
     * @return bool
     */
    public function isBackupEnvironment(): bool
    {
        return $this->backupEnvironment;
    }

    /**
     * @param bool $backupEnvironment
     */
    public function setBackupEnvironment(bool $backupEnvironment): void
    {
        $this->backupEnvironment = $backupEnvironment;
    }

    /**
     * @return bool
     */
    public function isResetSingletonInstances(): bool
    {
        return $this->resetSingletonInstances;
    }

    /**
     * @param bool $resetSingletonInstances
     */
    public function setResetSingletonInstances(bool $resetSingletonInstances): void
    {
        $this->resetSingletonInstances = $resetSingletonInstances;
    }

    /**
     * @param string $testFilesToDelete
     */
    public function addTestFilesToDelete(string $testFilesToDelete): void
    {
        $this->testFilesToDelete[] = $testFilesToDelete;
    }

    /**
     * Generic setUp()
     */
    public function setUp()
    {
        if ($this->backupEnvironment === true) {
            $this->backupEnvironment();
        }
        $GLOBALS['TYPO3_CONF_VARS']['LOG'] = [];
        $file = getcwd() . '/Tests/_data/local.db';
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'] = [
            'driver' => 'pdo_sqlite',
            'url' => 'sqlite3:////' . $file,
        ];
    }

    /**
     * This internal variable tracks if the given test is the first test of
     * that test case. This variable is set to current calling test case class.
     * Consecutive tests then optimize and do not create a full
     * database structure again but instead just truncate all tables which
     * is much quicker.
     *
     * @var string
     */
    private static $currestTestCaseClass;

    /**
     * Array of folders that should be created inside the test instance document root.
     *
     * This property will stay empty in this abstract, so it is possible
     * to just overwrite it in extending classes. Path noted here will
     * be linked for every test of a test case and it is not possible to change
     * the list of folders between single tests of a test case.
     *
     * Per default the following folder are created
     * /fileadmin
     * /typo3temp
     * /typo3conf
     * /typo3conf/ext
     * /uploads
     *
     * To create additional folders add the paths to this array. Given paths are expected to be
     * relative to the test instance root and have to begin with a slash. Example:
     *
     * [
     *   'fileadmin/user_upload'
     * ]
     *
     * @var array
     */
    protected $additionalFoldersToCreate = [];

    /**
     * Array of test/fixture extensions paths that should be loaded for a test.
     *
     * This property will stay empty in this abstract, so it is possible
     * to just overwrite it in extending classes. Extensions noted here will
     * be loaded for every test of a test case and it is not possible to change
     * the list of loaded extensions between single tests of a test case.
     *
     * Given path is expected to be relative to your document root, example:
     *
     * array(
     *   'typo3conf/ext/some_extension/Tests/Functional/Fixtures/Extensions/test_extension',
     *   'typo3conf/ext/base_extension',
     * );
     *
     * Extensions in this array are linked to the test instance, loaded
     * and their ext_tables.sql will be applied.
     *
     * @var string[]
     */
    protected $testExtensionsToLoad = [];

    /**
     * @return string[]
     */
    public function getTestExtensionsToLoad(): array
    {
        return $this->testExtensionsToLoad;
    }

    /**
     * @param string[] $testExtensionsToLoad
     */
    public function setTestExtensionsToLoad(array $testExtensionsToLoad): void
    {
        $this->testExtensionsToLoad = $testExtensionsToLoad;
    }

    /**
     * Same as $testExtensionsToLoad, but included per default from the testing framework.
     *
     * @var string[]
     */
    protected $frameworkExtensionsToLoad = [
        'Resources/Core/Functional/Extensions/json_response',
    ];
    /**
     * Array of test/fixture folder or file paths that should be linked for a test.
     *
     * This property will stay empty in this abstract, so it is possible
     * to just overwrite it in extending classes. Path noted here will
     * be linked for every test of a test case and it is not possible to change
     * the list of folders between single tests of a test case.
     *
     * array(
     *   'link-source' => 'link-destination'
     * );
     *
     * Given paths are expected to be relative to the test instance root.
     * The array keys are the source paths and the array values are the destination
     * paths, example:
     *
     * [
     *   'typo3/sysext/impext/Tests/Functional/Fixtures/Folders/fileadmin/user_upload' =>
     *   'fileadmin/user_upload',
     *   'typo3conf/ext/my_own_ext/Tests/Functional/Fixtures/Folders/uploads/tx_myownext' =>
     *   'uploads/tx_myownext'
     * ]
     *
     * To be able to link from my_own_ext the extension path needs also to be registered in
     * property $testExtensionsToLoad
     *
     * @var string[]
     */
    protected $pathsToLinkInTestInstance = [];

    /**
     * Similar to $pathsToLinkInTestInstance, with the difference that given
     * paths are really duplicated and provided in the instance - instead of
     * using symbolic links. Examples:
     *
     * [
     *   // Copy an entire directory recursive to fileadmin
     *   'typo3/sysext/lowlevel/Tests/Functional/Fixtures/testImages/' => 'fileadmin/',
     *   // Copy a single file into some deep destination directory
     *   'typo3/sysext/lowlevel/Tests/Functional/Fixtures/testImage/someImage.jpg' => 'fileadmin/_processed_/0/a/someImage.jpg',
     * ]
     *
     * @var string[]
     */
    protected $pathsToProvideInTestInstance = [];

    /**
     * This configuration array is merged with TYPO3_CONF_VARS
     * that are set in default configuration and factory configuration
     *
     * @var array
     */
    protected $configurationToUseInTestInstance = [];

    /**
     * Core extensions to load.
     *
     * If the test case needs additional core extensions as requirement,
     * they can be noted here and will be added to LocalConfiguration
     * extension list and ext_tables.sql of those extensions will be applied.
     *
     * This property will stay empty in this abstract, so it is possible
     * to just overwrite it in extending classes. Extensions noted here will
     * be loaded for every test of a test case and it is not possible to change
     * the list of loaded extensions between single tests of a test case.
     *
     * A default list of core extensions is always loaded.
     *
     * @see FunctionalTestCaseUtility $defaultActivatedCoreExtensions
     * @var array
     */
    protected $coreExtensionsToLoad = [];

    /**
     * @throws \Throwable
     */
    public function setUpFunctional()
    {
        if (!defined('ORIGINAL_ROOT')) {
            define('ORIGINAL_ROOT', getcwd() . '/.Build/public/');
        }
        if (!defined('LF')) {
            define('LF', "\n");
        }
        $instancePath = self::getInstancePath();
        putenv('TYPO3_PATH_ROOT=' . $instancePath);
        putenv('TYPO3_PATH_APP=' . $instancePath);

        $testbase = new Testbase();
        $testbase->defineTypo3ModeBe();
        $testbase->definePackagesPath();
        $testbase->setTypo3TestingContext();

        $isFirstTest = false;
        $currentTestCaseClass = get_called_class();
        if (self::$currestTestCaseClass !== $currentTestCaseClass) {
            $isFirstTest = true;
            self::$currestTestCaseClass = $currentTestCaseClass;
        }

        if (!$isFirstTest) {
            // Reusing an existing instance. This typically happens for the second, third, ... test
            // in a test case, so environment is set up only once per test case.
            GeneralUtility::purgeInstances();
            $testbase->setUpBasicTypo3Bootstrap($instancePath);
            $testbase->initializeTestDatabaseAndTruncateTables();
            Bootstrap::initializeBackendRouter();
            $testbase->loadExtensionTables();
        } else {
            $testbase->removeOldInstanceIfExists($instancePath);
            // Basic instance directory structure
            $testbase->createDirectory($instancePath . '/fileadmin');
            $testbase->createDirectory($instancePath . '/typo3temp/var/transient');
            $testbase->createDirectory($instancePath . '/typo3temp/assets');
            $testbase->createDirectory($instancePath . '/typo3conf/ext');
            $testbase->createDirectory($instancePath . '/uploads');
            // Additionally requested directories
            foreach ($this->additionalFoldersToCreate as $directory) {
                $testbase->createDirectory($instancePath . '/' . $directory);
            }
            $testbase->setUpInstanceCoreLinks($instancePath);
            $testbase->linkTestExtensionsToInstance($instancePath, $this->testExtensionsToLoad);
            $testbase->linkFrameworkExtensionsToInstance($instancePath, $this->frameworkExtensionsToLoad);
            $testbase->linkPathsInTestInstance($instancePath, $this->pathsToLinkInTestInstance);
            $testbase->providePathsInTestInstance($instancePath, $this->pathsToProvideInTestInstance);

            // Set some hard coded base settings for the instance. Those could be overruled by
            // $this->configurationToUseInTestInstance if needed again.
            $localConfiguration['SYS']['displayErrors'] = '1';
            $localConfiguration['SYS']['debugExceptionHandler'] = '';
            $localConfiguration['SYS']['trustedHostsPattern'] = '.*';
            $localConfiguration['SYS']['encryptionKey'] = 'i-am-not-a-secure-encryption-key';
            $localConfiguration['SYS']['caching']['cacheConfigurations']['extbase_object']['backend'] = NullBackend::class;
            $testbase->setUpLocalConfiguration($instancePath, $localConfiguration, $this->configurationToUseInTestInstance);


            $defaultCoreExtensionsToLoad = [
                'core',
                'backend',
                'frontend',
                'extbase',
                'install',
                'recordlist',
            ];
            $testbase->setUpPackageStates(
                $instancePath,
                $defaultCoreExtensionsToLoad,
                $this->coreExtensionsToLoad,
                $this->testExtensionsToLoad,
                $this->frameworkExtensionsToLoad
            );
            $file = realpath(__DIR__ . '/../../_data/local.db');
            $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'] = [
                'driver' => 'pdo_sqlite',
                'url' => 'sqlite3:////' . $file,
            ];

            $code = var_export($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'], true);

            $php = <<<PHP
<?php
\$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'] = $code;
PHP;

            $file = $instancePath . '/typo3conf/AdditionalConfiguration.php';
            file_put_contents($file, $php);
            $testbase->setUpBasicTypo3Bootstrap($instancePath);
            Bootstrap::initializeBackendRouter();
            $testbase->loadExtensionTables();
//            $testbase->createDatabaseStructure();
        }
    }

    /**
     * Uses a 7 char long hash of class name as identifier.
     *
     * @return string
     */
    protected static function getInstanceIdentifier(): string
    {
        return substr(sha1(static::class), 0, 7);
    }

    /**
     * @return string
     */
    protected static function getInstancePath(): string
    {
        $identifier = self::getInstanceIdentifier();
        return ORIGINAL_ROOT . 'typo3temp/var/tests/functional-' . $identifier;
    }

    /**
     * Unset all additional properties of test classes to help PHP
     * garbage collection. This reduces memory footprint with lots
     * of tests.
     *
     * If overwriting tearDown() in test classes, please call
     * parent::tearDown() at the end. Unsetting of own properties
     * is not needed this way.
     *
     * @return void
     * @throws RuntimeException
     */
    public function tearDown()
    {
        unset($GLOBALS['TCA']);
        // Restore Environment::class is asked for
        if ($this->backupEnvironment === true) {
            $this->restoreEnvironment();
        }

        // Flush the two static $indpEnvCache and $idnaStringCache
        // between test runs to prevent side effects from these caches.
        GeneralUtility::flushInternalRuntimeCaches();

        // GeneralUtility::makeInstance() singleton handling
        if ($this->resetSingletonInstances === true) {
            // Reset singletons if asked for by test setup
            GeneralUtility::resetSingletonInstances([]);
        } else {
            // But fail if there are instances left and the test did not ask for reset
            $singletonInstances = GeneralUtility::getSingletonInstances();
            // Reset singletons anyway to not let all futher tests fail
            GeneralUtility::resetSingletonInstances([]);
            self::assertEmpty(
                $singletonInstances,
                'tearDown() integrity check found left over singleton instances in GeneralUtilily::makeInstance()'
                . ' instance list. The test should probably set \'$this->resetSingletonInstances = true;\' to'
                . ' reset this framework state change. Found singletons: ' . implode(
                    ', ',
                    array_keys($singletonInstances)
                )
            );
        }

        // Delete registered test files and directories
        foreach ($this->testFilesToDelete as $absoluteFileName) {
            $absoluteFileName = GeneralUtility::fixWindowsFilePath(PathUtility::getCanonicalPath($absoluteFileName));
            if (!GeneralUtility::validPathStr($absoluteFileName)) {
                throw new RuntimeException('tearDown() cleanup: Filename contains illegal characters', 1410633087);
            }
            if (strpos($absoluteFileName, PATH_site . 'typo3temp/var/') !== 0) {
                throw new RuntimeException(
                    'tearDown() cleanup:  Files to delete must be within typo3temp/var/',
                    1410633412
                );
            }
            // file_exists returns false for links pointing to not existing targets, so handle links before next check.
            if (@is_link($absoluteFileName) || @is_file($absoluteFileName)) {
                unlink($absoluteFileName);
            } elseif (@is_dir($absoluteFileName)) {
                GeneralUtility::rmdir($absoluteFileName, true);
            } else {
                throw new RuntimeException('tearDown() cleanup: File, link or directory does not exist', 1410633510);
            }
        }
        $this->testFilesToDelete = [];

        // Verify all instances a test may have added using addInstance() have
        // been consumed from the GeneralUtility::makeInstance() instance stack.
        // This integrity check is to avoid side effects on tests run afterwards.
        $instanceObjectsArray = GeneralUtility::getInstances();
        $notCleanInstances = [];
        foreach ($instanceObjectsArray as $instanceObjectArray) {
            if (!empty($instanceObjectArray)) {
                foreach ($instanceObjectArray as $instance) {
                    $notCleanInstances[] = $instance;
                }
            }
        }
        // Let the test fail if there were instances left and give some message on why it fails
        self::assertEquals(
            [],
            $notCleanInstances,
            'tearDown() integrity check found left over instances in GeneralUtility::makeInstance() instance list.'
            . ' Always consume instances added via GeneralUtility::addInstance() in your test by the test subject.'
        );

        // Verify LocalizationUtility class internal state has been reset properly if a test fiddled with it
        $reflectionClass = new ReflectionClass(LocalizationUtility::class);
        $property = $reflectionClass->getProperty('configurationManager');
        $property->setAccessible(true);
        self::assertNull($property->getValue());
    }

    /**
     * Helper method used in setUp() if $this->backupEnvironment is true
     * to back up current state of the Environment::class
     */
    private function backupEnvironment(): void
    {
        $this->backedUpEnvironment['context'] = Environment::getContext();
        $this->backedUpEnvironment['isCli'] = Environment::isCli();
        $this->backedUpEnvironment['composerMode'] = Environment::isComposerMode();
        $this->backedUpEnvironment['projectPath'] = Environment::getProjectPath();
        $this->backedUpEnvironment['publicPath'] = Environment::getPublicPath();
        $this->backedUpEnvironment['varPath'] = Environment::getVarPath();
        $this->backedUpEnvironment['configPath'] = Environment::getConfigPath();
        $this->backedUpEnvironment['currentScript'] = Environment::getCurrentScript();
        $this->backedUpEnvironment['isOsWindows'] = Environment::isWindows();
    }

    /**
     * Helper method used in tearDown() if $this->backupEnvironment is true
     * to reset state of Environment::class
     */
    private function restoreEnvironment(): void
    {
        Environment::initialize(
            $this->backedUpEnvironment['context'],
            $this->backedUpEnvironment['isCli'],
            $this->backedUpEnvironment['composerMode'],
            $this->backedUpEnvironment['projectPath'],
            $this->backedUpEnvironment['publicPath'],
            $this->backedUpEnvironment['varPath'],
            $this->backedUpEnvironment['configPath'],
            $this->backedUpEnvironment['currentScript'],
            $this->backedUpEnvironment['isOsWindows'] ? 'WINDOWS' : 'UNIX'
        );
    }
}
