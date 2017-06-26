<?php
namespace In2code\In2publishCore\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 in2code.de
 *  Alex Kellner <alexander.kellner@in2code.de>,
 *  Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use In2code\In2publishCore\Service\Configuration\In2publishConfigurationService;
use In2code\In2publishCore\Service\Context\ContextService;
use TYPO3\CMS\Backend\Utility\BackendUtility as CoreBackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ConfigurationUtility
 */
class ConfigurationUtility
{
    /**********************
     *                    *
     *   PUBLIC METHODS   *
     *                    *
     **********************/

    /**
     * @return string
     */
    public static function getFileConfigurationHash()
    {
        return self::getInstance()->getFileConfigurationHashInternal();
    }

    /**
     * Get a configuration attribute
     *
     * @param string $configurationPath
     * @return mixed
     */
    public static function getConfiguration($configurationPath = '')
    {
        return self::getInstance()->getConfigurationInternal($configurationPath);
    }

    /**
     * Get a configuration attribute
     *
     * @return array
     */
    public static function getPublicConfiguration()
    {
        return ArrayUtility::removeFromArrayByKey(self::getConfiguration(), self::$privateConfiguration);
    }

    /**
     * @return string
     */
    public static function getLoadingState()
    {
        return self::getInstance()->getLoadingStateInternal();
    }

    /**
     * @return bool
     */
    public static function isConfigurationLoadedSuccessfully()
    {
        return self::getInstance()->isConfigurationLoadedSuccessfullyInternal();
    }

    /**********************
     *                    *
     *  INTERNAL METHODS  *
     *                    *
     **********************/

    /**
     * @return string
     */
    protected function getFileConfigurationHashInternal()
    {
        return sha1(serialize($this->configurationCache[self::CACHE_KEY_FILE]));
    }

    /**
     * @param string $configurationPath
     * @return array|string
     * @throws \Exception
     */
    protected function getConfigurationInternal($configurationPath)
    {
        $configuration = $this->getMergedConfiguration();

        if (!empty($configurationPath)) {
            return ArrayUtility::getValueByPath($configuration, $configurationPath);
        }
        return $configuration;
    }

    /**
     * @return string
     */
    protected function getLoadingStateInternal()
    {
        return $this->loadingMessage;
    }

    /**
     * @return bool
     */
    protected function isConfigurationLoadedSuccessfullyInternal()
    {
        return $this->loadingMessage === self::STATE_LOADED;
    }

    /**********************
     *                    *
     *      SINGLETON     *
     *                    *
     **********************/

    /**
     * @var ConfigurationUtility
     */
    private static $instance = null;

    /**
     * @var ContextService
     */
    protected $contextService = null;

    /**
     * ConfigurationUtility constructor.
     */
    private function __construct()
    {
        $this->contextService = GeneralUtility::makeInstance(
            ContextService::class
        );
    }

    /**
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * @return ConfigurationUtility
     */
    private static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self;
            self::$instance->initialize();
        }
        return self::$instance;
    }

    /**********************
     *                    *
     *     INITIALIZE     *
     *                    *
     **********************/

    const STATE_NOT_LOADED = 'configuration.not_loaded';
    const STATE_EXT_CONF_NOT_AVAILABLE = 'configuration.ext_conf_not_available';
    const STATE_FILE_NOT_EXISTING = 'configuration.configuration_file_missing';
    const STATE_FILE_NOT_READABLE = 'configuration.configuration_file_not_readable';
    const STATE_PATH_NOT_EXISTING = 'configuration.configuration_path_not_existing';
    const STATE_LOADED = 'configuration.loaded';
    const CONFIGURATION_FILE_PATTERN = '%sConfiguration.yaml';
    const VERSIONED_CONFIGURATION_FILE_PATTERN = '%sConfiguration_%s.yaml';
    const PATH_TO_CONFIGURATION = 'pathToConfiguration';
    const CACHE_KEY_FILE = 'file';
    const CACHE_KEY_PAGE = 'page';
    const CACHE_KEY_USER = 'user';
    const DATABASE = 'database';
    const SSH_CONNECTION = 'sshConnection';

    /**
     * Private configuration keys which are security relevant
     *
     * @var array
     */
    protected static $privateConfiguration = array(
        self::DATABASE,
        self::SSH_CONNECTION,
    );

    /**
     * @var string
     */
    protected $loadingMessage = self::STATE_NOT_LOADED;

    /**
     * Stores the configuration in any overwritten state
     *
     * @var array
     */
    protected $configurationCache = array(
        self::CACHE_KEY_FILE => array(),
        self::CACHE_KEY_PAGE => null,
        self::CACHE_KEY_USER => null,
    );

    /**
     * @return array
     */
    protected function getMergedConfiguration()
    {
        if ($this->configurationCache[self::CACHE_KEY_USER] !== null) {
            $configuration = $this->configurationCache[self::CACHE_KEY_USER];
        } elseif ($this->configurationCache[self::CACHE_KEY_PAGE] !== null) {
            $configuration = $this->configurationCache[self::CACHE_KEY_PAGE];
            $configuration = $this->mergeUserTs($configuration);
        } else {
            $configuration = $this->configurationCache[self::CACHE_KEY_FILE];
            $configuration = $this->mergePageTs($configuration);
            $configuration = $this->mergeUserTs($configuration);
        }
        return $configuration;
    }

    /**
     * @param array $configuration
     * @return array
     */
    protected function mergeUserTs(array $configuration)
    {
        if ($this->configurationCache[self::CACHE_KEY_USER] === null) {
            // try merge userTS the first time BE_USER is available
            if ($this->isBackendUserInitialized()) {
                if ($this->isUserTsConfigEnabled()) {
                    $userTs = GeneralUtility::removeDotsFromTS(CoreBackendUtility::getModTSconfig(0, 'tx_in2publish'));
                    $userTs = ArrayUtility::normalizeArray((array)$userTs['properties']);
                    $configuration = $this->merge($configuration, $userTs);
                }

                // set the cache if userTS is disabled or it was merged
                $this->configurationCache[self::CACHE_KEY_USER] = $configuration;
            }
        } else {
            // set to the cached config (userTS: FALSE || MERGED)
            $configuration = $this->configurationCache[self::CACHE_KEY_USER];
        }
        // return the cached config (BE_USER unavailable || userTS MERGED)
        return $configuration;
    }

    /**
     * @param array $configuration
     * @return array
     */
    protected function mergePageTs(array $configuration)
    {
        $uid = BackendUtility::getPageIdentifier();
        if ($this->configurationCache[self::CACHE_KEY_PAGE] === null) {
            // get the pageTS | Manually pass rootline to disable caching.
            $pageTs = CoreBackendUtility::getPagesTSconfig($uid, CoreBackendUtility::BEgetRootLine($uid));

            // if there is any in2publish config in the pageTS
            if (!empty($pageTs['tx_in2publish.'])) {
                $pageTs = ArrayUtility::normalizeArray(GeneralUtility::removeDotsFromTS($pageTs['tx_in2publish.']));
                $configuration = $this->merge($configuration, $pageTs);
            }
            $this->configurationCache[self::CACHE_KEY_PAGE] = $configuration;
        } else {
            $configuration = $this->configurationCache[self::CACHE_KEY_PAGE];
        }
        return $configuration;
    }

    /**
     * @param array $configuration
     * @param array $overwrite
     * @return array
     */
    protected function merge(array $configuration, array $overwrite)
    {
        \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
            $configuration,
            ArrayUtility::removeFromArrayByKey($overwrite, self::$privateConfiguration),
            true
        );
        return $configuration;
    }

    /**
     * @return bool
     */
    protected function isUserTsConfigEnabled()
    {
        return (
            isset($this->configurationCache[self::CACHE_KEY_FILE]['disableUserConfig'])
            && $this->configurationCache[self::CACHE_KEY_FILE]['disableUserConfig'] === false
        );
    }

    /**
     * @return bool
     */
    protected function initialize()
    {
        $pathToConfiguration = GeneralUtility::makeInstance(
            In2publishConfigurationService::class
        )->getPathToConfiguration();

        if (null === $pathToConfiguration) {
            $this->loadingMessage = self::STATE_EXT_CONF_NOT_AVAILABLE;
            return false;
        } elseif (strpos($pathToConfiguration, '/') !== 0 && strpos($pathToConfiguration, '../') !== 0) {
            $pathToConfiguration = GeneralUtility::getFileAbsFileName($pathToConfiguration);
        } elseif (strpos($pathToConfiguration, '../') === 0) {
            $pathToConfiguration = PATH_site . $pathToConfiguration;
        }

        $pathToConfiguration = realpath(rtrim($pathToConfiguration, '/')) . '/';

        $configurationFile = $this->resolveConfigurationFile($pathToConfiguration);

        if (empty($pathToConfiguration)) {
            $this->loadingMessage = self::STATE_PATH_NOT_EXISTING;
        } elseif (!file_exists($configurationFile)) {
            $this->loadingMessage = self::STATE_FILE_NOT_EXISTING;
        } elseif (!is_readable($configurationFile)) {
            $this->loadingMessage = self::STATE_FILE_NOT_READABLE;
        } else {
            $this->configurationCache[self::CACHE_KEY_FILE] = \Spyc::YAMLLoad($configurationFile);
            $this->loadingMessage = self::STATE_LOADED;
            return true;
        }
        return false;
    }

    /**
     * Get absolute path and filename of the configuration file
     *
     * @param string $path defined path for configuration files
     * @return string
     */
    protected function resolveConfigurationFile($path)
    {
        $versionedConfigFile = $path . $this->getVersionedFilename();
        if (file_exists($versionedConfigFile) && is_readable($versionedConfigFile)) {
            return $versionedConfigFile;
        }
        return $path . $this->getFilename();
    }

    /**
     * Get default configuration path and filename
     *
     * @return string $path LocalConfiguration.yaml or ForeignConfiguration.yaml
     */
    protected function getFilename()
    {
        return sprintf(self::CONFIGURATION_FILE_PATTERN, $this->contextService->getContext());
    }

    /**
     * Get versioned configuration path and filename
     *
     * @return string $path e.g. LocalConfiguration_1.2.3.yaml or ForeignConfiguration_4.2.9.yaml
     */
    protected function getVersionedFilename()
    {
        return sprintf(
            self::VERSIONED_CONFIGURATION_FILE_PATTERN,
            $this->contextService->getContext(),
            $this->getIn2publishCoreVersion()
        );
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    protected function getIn2publishCoreVersion()
    {
        return ExtensionManagementUtility::getExtensionVersion('in2publish_core');
    }

    /**
     * @return bool
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function isBackendUserInitialized()
    {
        return ($GLOBALS['BE_USER'] instanceof BackendUserAuthentication);
    }
}
