<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\ConfigContainer\Factory;

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

use In2code\In2publishCore\Component\ConfigContainer\ConditionalConfigServiceInterface;
use In2code\In2publishCore\Component\ConfigContainer\ConfigContainer;
use In2code\In2publishCore\Component\ConfigContainer\Definer\DefinerServiceInterface;
use In2code\In2publishCore\Component\ConfigContainer\Migration\MigrationServiceInterface;
use In2code\In2publishCore\Component\ConfigContainer\PostProcessor\PostProcessorServiceInterface;
use In2code\In2publishCore\Component\ConfigContainer\Provider\ProviderServiceInterface;

class ConfigContainerFactory
{
    /** @var array<class-string<ProviderServiceInterface>, ProviderServiceInterface> */
    private array $providerServices;
    /** @var array<class-string<DefinerServiceInterface>, DefinerServiceInterface> */
    private array $definerServices;
    /** @var array<class-string<MigrationServiceInterface>, MigrationServiceInterface> */
    private array $migrationServices;
    /** @var array<class-string<PostProcessorServiceInterface>, PostProcessorServiceInterface> */
    private array $postProcessorServices;
    /** @var array<string, array<ConditionalConfigServiceInterface>> */
    private array $removedServices = [];

    public function __construct(
        array $providerServices,
        array $definerServices,
        array $migrationServices,
        array $postProcessorServices
    ) {
        $this->providerServices = $providerServices;
        $this->definerServices = $definerServices;
        $this->migrationServices = $migrationServices;
        $this->postProcessorServices = $postProcessorServices;
    }

    /**
     * @param array<object> $services
     * @return array<object>
     */
    protected function filterDisabledServices(array $services, string $key): array
    {
        foreach ($services as $index => $service) {
            if ($service instanceof ConditionalConfigServiceInterface && !$service->isEnabled()) {
                $this->removedServices[$key][$index] = $service;
                unset($services[$index]);
            }
        }
        return $services;
    }

    public function getRemovedServices(): array
    {
        return $this->removedServices;
    }

    public function create(): ConfigContainer
    {
        return new ConfigContainer(
            $this->filterDisabledServices($this->providerServices, 'provider'),
            $this->filterDisabledServices($this->definerServices, 'definer'),
            $this->filterDisabledServices($this->migrationServices, 'migration'),
            $this->filterDisabledServices($this->postProcessorServices, 'postProcessor'),
        );
    }
}
