<?php

declare(strict_types=1);

use In2code\In2publishCore\Features\AdminTools\DependencyInjection\AdminToolCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

return static function (ContainerBuilder $builder): void {
    $builder->addCompilerPass(new AdminToolCompilerPass('in2publish_core.admin_tool'));
};
