<?php
namespace In2code\In2publishCore\Features\LogsIntegration\Controller;

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

use In2code\In2publishCore\Domain\Service\ExecutionTimeService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class LogController
 */
class LogController extends \VerteXVaaR\Logs\Controller\LogController
{
    /**
     * TODO: Check if the configuration can be accessed and merged somewhere else
     *
     * @var array
     */
    protected $txLogsViewConfig = [
        'templateRootPaths' => [
            20 => 'EXT:in2publish_core/Resources/Private/Templates',
        ],
        'partialRootPaths' => [
            20 => 'EXT:in2publish_core/Resources/Private/Partials',
        ],
        'layoutRootPaths' => [
            20 => 'EXT:in2publish_core/Resources/Private/Layouts',
        ],
    ];

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function initializeAction()
    {
        parent::initializeAction();
        GeneralUtility::makeInstance(ExecutionTimeService::class)->start();
        $this->logConfiguration = $GLOBALS['TYPO3_CONF_VARS']['LOG']['In2code']['In2publishCore'];

        $config = $this->configurationManager->getConfiguration('FullTypoScript');
        ArrayUtility::mergeRecursiveWithOverrule(
            $this->txLogsViewConfig,
            GeneralUtility::removeDotsFromTS($config['module.']['tx_logs.']['view.'])
        );
    }

    /**
     * @param array $extbaseConfig
     * @param string $setting
     *
     * @return array
     */
    protected function getViewProperty($extbaseConfig, $setting)
    {
        if (isset($this->txLogsViewConfig[$setting])) {
            ksort($this->txLogsViewConfig[$setting]);
            return array_reverse($this->txLogsViewConfig[$setting]);
        }
        return parent::getViewProperty($extbaseConfig, $setting);
    }
}
