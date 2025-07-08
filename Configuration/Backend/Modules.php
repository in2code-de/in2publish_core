<?php

declare(strict_types=1);

/*
 * Copyright notice
 *
 * (c) 2023 in2code.de and the following authors:
 * Daniel Hoffmann <daniel.hoffmann@in2code.de>
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

use In2code\In2publishCore\Component\ConfigContainer\ConfigContainer;
use In2code\In2publishCore\Controller\FileController;
use In2code\In2publishCore\Controller\RecordController;
use In2code\In2publishCore\Features\AdminTools\Service\ToolsRegistry;
use In2code\In2publishCore\Features\RedirectsSupport\Controller\RedirectController;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$backendModulesToRegister = [];

/** @var ConfigContainer $configContainer */
$configContainer = GeneralUtility::makeInstance(ConfigContainer::class);

if ($configContainer->get('module.m1')) {
    $backendModulesToRegister['in2publish_core_m1'] = [
        'parent' => 'web',
        'aliases' => ['web_In2publishCoreM1'],
        'position' => [],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/in2publish_core/m1',
        'labels' => 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang_mod1.xlf',
        'extensionName' => 'in2publish_core',
        'iconIdentifier' => 'in2publish-core-overview-module',
        'controllerActions' => [
            RecordController::class => ['index', 'detail', 'publishRecord', 'toggleFilterStatus'],
        ],
    ];
}

if ($configContainer->get('module.m3')) {
    $backendModulesToRegister['in2publish_core_m3'] = [
        'parent' => 'file',
        'aliases' => ['file_In2publishCoreM3'],
        'position' => [],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/in2publish_core/m3',
        'labels' => 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang_mod3.xlf',
        'extensionName' => 'in2publish_core',
        'iconIdentifier' => 'in2publish-core-file-module',
        'controllerActions' => [
            FileController::class => ['index', 'publishFolder', 'publishFile', 'toggleFilterStatus'],
        ],
    ];
}

if ($configContainer->get('module.m4')) {
    $toolsRegistry = GeneralUtility::makeInstance(ToolsRegistry::class);
    $controllerActions = $toolsRegistry->processData();
    if (!empty($controllerActions)) {
        $backendModulesToRegister['in2publish_core_m4'] = [
            'parent' => 'tools',
            'aliases' => ['tools_In2publishCoreM4'],
            'position' => [],
            'access' => 'admin',
            'workspaces' => 'live',
            'path' => '/module/in2publish_core/m4',
            'labels' => 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang_mod4.xlf',
            'extensionName' => 'in2publish_core',
            'iconIdentifier' => 'in2publish-core-tools-module',
            'controllerActions' => $controllerActions,
        ];
    }
}

if ($configContainer->get('features.redirectsSupport.enable')) {
    $backendModulesToRegister['in2publish_core_m5'] = [
        'parent' => 'site',
        'aliases' => ['site_In2publishCoreM5'],
        'position' => ['after' => 'site_redirects'],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/in2publish_core/m5',
        'labels' => 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang_mod5.xlf',
        'extensionName' => 'in2publish_core',
        'iconIdentifier' => 'in2publish-core-redirect-module',
        'controllerActions' => [
            RedirectController::class => ['list','publish','selectSite'],
        ],
    ];
}

return $backendModulesToRegister;
