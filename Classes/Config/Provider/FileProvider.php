<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Config\Provider;

/*
 * Copyright notice
 *
 * (c) 2018 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use In2code\In2publishCore\Service\Context\ContextService;
use Spyc;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException as ExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException as ExtConfPathDoesNotExist;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function class_exists;
use function file_exists;
use function rtrim;
use function strpos;
use function substr;
use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * Class FileProvider
 */
class FileProvider implements ProviderInterface
{
    protected const DEPRECATION_CONFIG_PATH_TYPO3CONF = 'Storing the content publisher config file in typo3conf is deprecated and considered insecure. Please consider storing your config in the TYPO3\'s config folder.';

    /**
     * @var ContextService
     */
    protected $contextService = null;

    /**
     * FileProvider constructor.
     */
    public function __construct()
    {
        $this->contextService = GeneralUtility::makeInstance(ContextService::class);
        if (!class_exists(Spyc::class)) {
            $spyc = ExtensionManagementUtility::extPath('in2publish_core', 'Resources/Private/Libraries/Spyc/Spyc.php');
            if (file_exists($spyc)) {
                require_once($spyc);
            }
        }
    }

    /**
     * @return bool
     */
    public function isAvailable()
    {
        return true;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        if (!class_exists(Spyc::class)) {
            return [];
        }

        $file = $this->getResolvedFilePath() . $this->contextService->getContext() . 'Configuration.yaml';

        if (file_exists($file)) {
            return Spyc::YAMLLoad($file);
        }

        return [];
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return 20;
    }

    /**
     * @return string
     */
    protected function getResolvedFilePath(): string
    {
        try {
            $path = $this->getConfiguredFilePath();
        } catch (ExtensionNotConfiguredException | ExtConfPathDoesNotExist $e) {
            $path = 'CONF:in2publish_core';
        }

        if (false !== strpos($path, 'typo3conf/')) {
            trigger_error(self::DEPRECATION_CONFIG_PATH_TYPO3CONF, E_USER_DEPRECATED);
        }

        if (0 === strpos($path, 'CONF:')) {
            $path = Environment::getConfigPath() . '/' . substr($path, 5);
        } elseif (0 !== strpos($path, '/') && 0 !== strpos($path, '../')) {
            $path = GeneralUtility::getFileAbsFileName($path);
        } elseif (0 === strpos($path, '../')) {
            $path = Environment::getPublicPath() . '/' . $path;
        }
        return rtrim($path, '/') . '/';
    }

    /**
     * @return string
     * @throws ExtensionNotConfiguredException
     * @throws ExtConfPathDoesNotExist
     */
    protected function getConfiguredFilePath(): string
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        return $extensionConfiguration->get('in2publish_core', 'pathToConfiguration');
    }
}
