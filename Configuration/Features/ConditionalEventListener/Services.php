<?php

declare(strict_types=1);

use In2code\In2publishCore\Features\ConditionalEventListener\DependencyInjection\ConditionalEventListenerCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;

return static function (ContainerBuilder $builder): void {
    $builder->addCompilerPass(
        new ConditionalEventListenerCompilerPass('in2publish_core.event.listener'),
        PassConfig::TYPE_BEFORE_OPTIMIZATION,
        75,
    );
};
