<?php

declare(strict_types=1);

use In2code\In2publishCore\Component\RecordHandling\DependencyInjection\RecordHandlingCompilerPass;
use In2code\In2publishCore\Component\RecordHandling\RecordFinder;
use In2code\In2publishCore\Component\RecordHandling\RecordPublisher;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator, ContainerBuilder $builder) {
    $builder->registerForAutoconfiguration(RecordFinder::class)->addTag('in2publish_core.record.finder');
    $builder->registerForAutoconfiguration(RecordPublisher::class)->addTag('in2publish_core.record.publisher');

    $builder->addCompilerPass(new RecordHandlingCompilerPass('in2publish_core.record.finder'));
    $builder->addCompilerPass(new RecordHandlingCompilerPass('in2publish_core.record.publisher'));
};
