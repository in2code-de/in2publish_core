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

use In2code\In2publishCore\Controller\FileController;
use In2code\In2publishCore\Controller\RecordController;
use In2code\In2publishCore\Features\AdminTools\Controller\LetterboxController;
use In2code\In2publishCore\Features\AdminTools\Controller\RegistryController;
use In2code\In2publishCore\Features\AdminTools\Controller\ShowConfigurationController;
use In2code\In2publishCore\Features\AdminTools\Controller\TcaController;
use In2code\In2publishCore\Features\AdminTools\Controller\TestController;
use In2code\In2publishCore\Features\AdminTools\Controller\ToolsController;
use In2code\In2publishCore\Features\CompareDatabaseTool\Controller\CompareDatabaseToolController;
use In2code\In2publishCore\Features\RecordInspector\Controller\RecordInspectorController;
use In2code\In2publishCore\Features\RedirectsSupport\Controller\RedirectController;
use In2code\In2publishCore\Features\SystemInformationExport\Controller\SystemInformationExportController;

// Note: ConfigContainer is not available during DI container compilation (TYPO3 v14).
// Config-based disabling can be done via BeforeModuleCreationEvent.
// Modules must not be registered on Foreign at all.
if ('Foreign' === (getenv('IN2PUBLISH_CONTEXT') ?: getenv('REDIRECT_IN2PUBLISH_CONTEXT') ?: '')) {
    return [];
}

return [
    'in2publish_core_m1' => [
        'parent' => 'content',
        'aliases' => ['web_In2publishCoreM1'],
        'position' => [],
        'access' => 'user',
        'workspaces' => 'live',
        'navigationComponent' => '@typo3/backend/tree/page-tree-element',
        'path' => '/module/in2publish_core/m1',
        'labels' => 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang_mod1.xlf',
        'extensionName' => 'in2publish_core',
        'iconIdentifier' => 'in2publish-core-overview-module',
        'controllerActions' => [
            RecordController::class => ['index', 'detail', 'publishRecord', 'toggleFilterStatus'],
        ],
    ],
    'in2publish_core_m3' => [
        'parent' => 'media',
        'aliases' => ['file_In2publishCoreM3'],
        'position' => [],
        'access' => 'user',
        'workspaces' => 'live',
        'navigationComponent' => '@typo3/backend/tree/file-storage-tree-container',
        'path' => '/module/in2publish_core/m3',
        'labels' => 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang_mod3.xlf',
        'extensionName' => 'in2publish_core',
        'iconIdentifier' => 'in2publish-core-file-module',
        'controllerActions' => [
            FileController::class => ['index', 'publishFolder', 'publishFile', 'toggleFilterStatus'],
        ],
    ],
    'in2publish_core_m4' => [
        'parent' => 'admin',
        'aliases' => ['tools_In2publishCoreM4'],
        'position' => [],
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/in2publish_core/m4',
        'labels' => 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang_mod4.xlf',
        'extensionName' => 'in2publish_core',
        'iconIdentifier' => 'in2publish-core-tools-module',
        'controllerActions' => [
            ToolsController::class => ['index'],
            LetterboxController::class => ['index', 'flushEnvelopes'],
            RegistryController::class => ['index', 'flushRegistry'],
            TcaController::class => ['index'],
            ShowConfigurationController::class => ['index'],
            TestController::class => ['index'],
            RecordInspectorController::class => ['index', 'inspect'],
            CompareDatabaseToolController::class => ['index', 'compare', 'transfer'],
            SystemInformationExportController::class => ['sysInfoIndex', 'sysInfoShow', 'sysInfoDecode', 'sysInfoDownload', 'sysInfoUpload'],
        ],
    ],
    'in2publish_core_m5' => [
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
            RedirectController::class => ['list', 'publish', 'selectSite'],
        ],
    ],
];
