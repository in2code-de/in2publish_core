<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\AdminTools\Service;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
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

use In2code\In2publishCore\Features\AdminTools\Service\Exception\ClassNotFoundException;
use In2code\In2publishCore\Service\Condition\ConditionEvaluationServiceInjection;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

use function class_exists;
use function implode;

class ToolsRegistry implements SingletonInterface
{
    use ConditionEvaluationServiceInjection;

    protected array $entries = [];

    public function addTool(
        string $serviceName,
        string $title,
        string $description,
        string $actions,
        ?string $condition = null
    ): void {
        $this->entries[$serviceName] = [
            'name' => $title,
            'description' => $description,
            'controller' => $serviceName,
            'action' => $actions,
            'condition' => $condition,
        ];
    }

    public function getEntries(): array
    {
        $processedTools = [];

        foreach ($this->entries as $key => $config) {
            if ($this->conditionEvaluationService->evaluate($config['condition'])) {
                $controller = $config['controller'];
                $processedTools[$key] = $config;
                $actions = GeneralUtility::trimExplode(',', $processedTools[$key]['action']);
                $processedTools[$key]['initialAction'] = $actions[0];
                $processedTools[$key]['alias'] = ExtensionUtility::resolveControllerAliasFromControllerClassName($controller);
            }
        }

        return $processedTools;
    }

    /**
     * @throws ClassNotFoundException
     */
    public function processData(): array
    {
        $controllerActions = [];
        foreach ($this->entries as $entry) {
            if ($this->conditionEvaluationService->evaluate($entry['condition'])) {
                $controllerName = $entry['controller'];
                $actions = GeneralUtility::trimExplode(',', $entry['action'], true);

                if (!class_exists($controllerName)) {
                    throw new ClassNotFoundException($controllerName, 2986116214);
                }

                foreach ($actions as $action) {
                    $controllerActions[$controllerName][] = $action;
                }
            }
        }

        return $controllerActions;
    }

    /**
     * @throws ClassNotFoundException
     * @deprecated Will be removed in TYPO3 v13
     */
    public function processDataForTypo3V11(): array
    {
        $controllerActions = $this->processData();
        foreach ($controllerActions as $controllerName => $actions) {
            $controllerActions[$controllerName] = implode(',', $actions);
        }
        return $controllerActions;
    }
}
