<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\AdminTools\DependencyInjection;

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

use In2code\In2publishCore\Features\AdminTools\DependencyInjection\Exception\MissingRequiredAttributesException;
use In2code\In2publishCore\Features\AdminTools\Service\ToolsRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_diff_key;
use function array_flip;

class AdminToolCompilerPass implements CompilerPassInterface
{
    private const REQUIRED_ATTRIBUTES = [
        'title',
        'description',
        'actions',
    ];
    private string $tagName;

    public function __construct(string $tagName)
    {
        $this->tagName = $tagName;
    }

    public function process(ContainerBuilder $container): void
    {
        $requiredAttributesAsKeys = array_flip(self::REQUIRED_ATTRIBUTES);

        $toolsRegistryDefinition = $container->findDefinition(ToolsRegistry::class);
        if (!$toolsRegistryDefinition) {
            return;
        }

        $unorderedAdminTools = [];
        foreach ($container->findTaggedServiceIds($this->tagName) as $serviceName => $tags) {
            $container->findDefinition($serviceName)->setPublic(true);
            foreach ($tags as $attributes) {
                $missingRequiredKeys = array_diff_key($requiredAttributesAsKeys, $attributes);
                if (!empty($missingRequiredKeys)) {
                    throw new MissingRequiredAttributesException(
                        $serviceName,
                        $this->tagName,
                        self::REQUIRED_ATTRIBUTES,
                        $missingRequiredKeys
                    );
                }

                $unorderedAdminTools[$serviceName] = [
                    'serviceName' => $serviceName,
                    'title' => $attributes['title'],
                    'description' => $attributes['description'],
                    'actions' => $attributes['actions'],
                    'condition' => $attributes['condition'] ?? null,
                    'before' => GeneralUtility::trimExplode(',', $attributes['before'] ?? '', true),
                    'after' => GeneralUtility::trimExplode(',', $attributes['after'] ?? '', true),
                ];
            }
        }

        $dependencyOrderingService = new DependencyOrderingService();
        $orderedAdminTools = $dependencyOrderingService->orderByDependencies($unorderedAdminTools);
        foreach ($orderedAdminTools as $adminTool) {
            $toolsRegistryDefinition->addMethodCall(
                'addTool',
                [
                    $adminTool['serviceName'],
                    $adminTool['title'],
                    $adminTool['description'],
                    $adminTool['actions'],
                    $adminTool['condition'],
                ]
            );
        }
    }
}
