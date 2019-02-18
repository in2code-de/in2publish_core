<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Config\Provider;

/***************************************************************
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
 ***************************************************************/

use In2code\In2publishCore\Service\Context\ContextService;
use Spyc;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function class_exists;
use function file_exists;
use function rtrim;
use function strpos;
use function unserialize;

/**
 * Class FileProvider
 */
class FileProvider implements ProviderInterface
{
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

        $file = $this->getConfigFilePath() . $this->contextService->getContext() . 'Configuration.yaml';

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
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function getConfigFilePath(): string
    {
        $path = 'typo3conf/AdditionalConfiguration/';
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['in2publish_core'])) {
            $extConf = @unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['in2publish_core']);
            if (isset($extConf['pathToConfiguration'])) {
                $path = $extConf['pathToConfiguration'];
            }
        }

        if (strpos($path, '/') !== 0 && strpos($path, '../') !== 0) {
            $path = GeneralUtility::getFileAbsFileName($path);
        } elseif (strpos($path, '../') === 0) {
            $path = PATH_site . $path;
        }
        return rtrim($path, '/') . '/';
    }
}
