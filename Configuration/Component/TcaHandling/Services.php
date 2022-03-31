<?php

declare(strict_types=1);

use In2code\In2publishCore\Component\TcaHandling\PreProcessing\DependencyInjection\TcaPreProcessorCompilerPass;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\TcaPreProcessor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator, ContainerBuilder $builder) {
    $builder->registerForAutoconfiguration(TcaPreProcessor::class)->addTag('in2publish_core.tca.preprocessor');

    $builder->addCompilerPass(new TcaPreProcessorCompilerPass('in2publish_core.tca.preprocessor'));
};
