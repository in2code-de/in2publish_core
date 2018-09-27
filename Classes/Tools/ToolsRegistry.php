<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Tools;

/***************************************************************
 * Copyright notice
 *
 * (c) 2017 in2code.de and the following authors:
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

use TYPO3\CMS\Core\Database\TableConfigurationPostProcessingHookInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

class ToolsRegistry implements SingletonInterface, TableConfigurationPostProcessingHookInterface
{
    /**
     * @var array[]
     */
    protected $entries = [];

    public function __construct()
    {
        $this->registerHookForPostProcessing();
    }

    /**
     * @param string $name
     * @param string $description
     * @param string $controller
     * @param string $action
     * @param string $extensionName
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function addTool($name, $description, $controller, $action, $extensionName = 'in2publish_core')
    {
        $this->entries[$name] = [
            'name' => $name,
            'description' => $description,
            'controller' => $controller,
            'action' => $action,
            'extensionName' => GeneralUtility::underscoredToUpperCamelCase($extensionName),
        ];
    }

    public function getTools(): array
    {
        return $this->entries;
    }

    public function removeTool(string $name)
    {
        unset($this->entries[$name]);
    }

    public function processData()
    {
        $controllerActions = [];
        foreach ($this->entries as $entry) {
            $controllerName = $entry['controller'];
            $actionName = $entry['action'];

            if (!isset($controllerActions[$controllerName])) {
                $controllerActions[$controllerName] = $actionName;
            } else {
                $controllerActions[$controllerName] .= ',' . $actionName;
            }
        }

        ExtensionUtility::registerModule(
            'In2code.In2publishCore',
            'tools',
            'm4',
            '',
            $controllerActions,
            [
                'access' => 'admin',
                'icon' => 'EXT:in2publish_core/Resources/Public/Icons/Tools.svg',
                'labels' => 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang_mod4.xlf',
            ]
        );
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function registerHookForPostProcessing()
    {
        $scOptions = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'];
        if (!isset($scOptions['GLOBAL']['extTablesInclusion-PostProcessing'][1517414708])) {
            $scOptions['GLOBAL']['extTablesInclusion-PostProcessing'][1517414708] = static::class;
        }
    }
}
