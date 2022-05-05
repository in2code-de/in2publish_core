<?php

declare(strict_types=1);

use In2code\In2publishCore\Component\TcaHandling\DependencyInjection\TcaPreProcessorCompilerPass;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\TcaPreProcessor;
use In2code\In2publishCore\Component\TcaHandling\Resolver\Resolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Core\DependencyInjection\PublicServicePass;

return static function (ContainerConfigurator $configurator, ContainerBuilder $builder) {
    $builder->registerForAutoconfiguration(TcaPreProcessor::class)->addTag('in2publish_core.tca.preprocessor');
    $builder->registerForAutoconfiguration(Resolver::class)->addTag('in2publish_core.tca.resolver');

    $builder->addCompilerPass(new TcaPreProcessorCompilerPass('in2publish_core.tca.preprocessor'));
    $builder->addCompilerPass(new PublicServicePass('in2publish_core.tca.resolver'));
};
