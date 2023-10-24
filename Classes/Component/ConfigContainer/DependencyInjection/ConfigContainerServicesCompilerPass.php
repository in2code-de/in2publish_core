<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\ConfigContainer\DependencyInjection;

/*
 * Copyright notice
 *
 * (c) 2023 in2code.de and the following authors:
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

use In2code\In2publishCore\Component\ConfigContainer\Factory\ConfigContainerFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use function array_keys;

class ConfigContainerServicesCompilerPass implements CompilerPassInterface
{
    private string $definerServiceTag;
    private string $migrationServiceTag;
    private string $postProcessorServiceTag;
    private string $providerServiceTag;

    public function __construct(
        string $definerServiceTag,
        string $migrationServiceTag,
        string $postProcessorServiceTag,
        string $providerServiceTag
    ) {
        $this->definerServiceTag = $definerServiceTag;
        $this->migrationServiceTag = $migrationServiceTag;
        $this->postProcessorServiceTag = $postProcessorServiceTag;
        $this->providerServiceTag = $providerServiceTag;
    }

    public function process(ContainerBuilder $container)
    {
        $configContainerFactory = $container->getDefinition(ConfigContainerFactory::class);

        foreach (
            [
                '$definerServices' => $this->definerServiceTag,
                '$migrationServices' => $this->migrationServiceTag,
                '$postProcessorServices' => $this->postProcessorServiceTag,
                '$providerServices' => $this->providerServiceTag,
            ] as $argument => $tag
        ) {
            $values = [];
            $serviceIds = $container->findTaggedServiceIds($tag);
            foreach (array_keys($serviceIds) as $serviceId) {
                $values[$serviceId] = new Reference($serviceId);
            }
            $configContainerFactory->setArgument($argument, $values);
        }
    }
}
