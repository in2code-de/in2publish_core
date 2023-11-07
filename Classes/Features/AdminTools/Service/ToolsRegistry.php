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

use In2code\In2publishCore\CommonInjection\ExtensionConfigurationInjection;
use In2code\In2publishCore\Component\ConfigContainer\ConfigContainerInjection;
use In2code\In2publishCore\Features\AdminTools\Service\Exception\ClassNotFoundException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

use function class_exists;
use function implode;

class ToolsRegistry implements SingletonInterface
{
    use ConfigContainerInjection;
    use ExtensionConfigurationInjection;

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
        // Do not inject the ConfigurationManager, because it will not contain the configured tools.
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        $configuration = $configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
        );
        $processedTools = [];

        $controllerConfig = $configuration['controllerConfiguration'];
        foreach ($this->entries as $key => $config) {
            if ($this->evaluateCondition($config)) {
                $controller = $config['controller'];
                $processedTools[$key] = $config;
                $actions = GeneralUtility::trimExplode(',', $processedTools[$key]['action']);
                $processedTools[$key]['initialAction'] = $actions[0];
                if (isset($controllerConfig[$controller]['alias'])) {
                    $processedTools[$key]['alias'] = $controllerConfig[$controller]['alias'];
                } else {
                    $controllerParts = explode('\\', $controller);
                    $processedTools[$key]['alias'] = str_replace('Controller', '', array_pop($controllerParts));
                }
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
            if ($this->evaluateCondition($entry)) {
                $controllerName = $entry['controller'];
                $actions = GeneralUtility::trimExplode(',', $entry['action'], true);

                if (!class_exists($controllerName)) {
                    throw new ClassNotFoundException($controllerName);
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

    protected function evaluateCondition(array $config): bool
    {
        if (null === $config['condition']) {
            return true;
        }
        $parts = GeneralUtility::trimExplode(':', $config['condition'], true);
        switch ($parts[0]) {
            case 'CONF':
                return (bool)$this->configContainer->get($parts[1]);
            case 'EXTCONF':
                return (bool)$this->extensionConfiguration->get($parts[1], $parts[2]);
        }
        return false;
    }
}
