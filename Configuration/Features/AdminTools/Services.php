<?php

declare(strict_types=1);

use In2code\In2publishCore\Features\AdminTools\DependencyInjection\AdminToolCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator, ContainerBuilder $builder) {
    $builder->addCompilerPass(new AdminToolCompilerPass('in2publish_core.admin_tool'));
};
