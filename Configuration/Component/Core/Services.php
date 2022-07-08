<?php

declare(strict_types=1);

use In2code\In2publishCore\Component\Core\DemandResolver\DemandResolver;
use In2code\In2publishCore\Component\Core\DependencyInjection\DemandResolverPass;
use In2code\In2publishCore\Component\Core\DependencyInjection\PublisherPass;
use In2code\In2publishCore\Component\Core\DependencyInjection\TcaPreProcessorPass;
use In2code\In2publishCore\Component\Core\PreProcessing\TcaPreProcessor;
use In2code\In2publishCore\Component\Core\Publisher\Publisher;
use In2code\In2publishCore\Component\Core\Resolver\Resolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Core\DependencyInjection\PublicServicePass;

return static function (ContainerConfigurator $configurator, ContainerBuilder $builder): void {
    $builder->registerForAutoconfiguration(TcaPreProcessor::class)->addTag('in2publish_core.tca.preprocessor');
    $builder->registerForAutoconfiguration(Resolver::class)->addTag('in2publish_core.tca.resolver');
    $builder->registerForAutoconfiguration(Publisher::class)->addTag('in2publish_core.publisher');
    $builder->registerForAutoconfiguration(DemandResolver::class)->addTag('in2publish_core.tca.demand_resolver');

    $builder->addCompilerPass(new TcaPreProcessorPass('in2publish_core.tca.preprocessor'));
    $builder->addCompilerPass(new PublicServicePass('in2publish_core.tca.resolver', true));
    $builder->addCompilerPass(new PublisherPass('in2publish_core.publisher'));
    $builder->addCompilerPass(new DemandResolverPass('in2publish_core.tca.demand_resolver'));
};
