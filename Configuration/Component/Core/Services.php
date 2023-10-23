<?php

declare(strict_types=1);

use In2code\In2publishCore\Component\Core\DemandResolver\DemandResolver;
use In2code\In2publishCore\Component\Core\DependencyInjection\DatabaseRecordFactoryFactoryCompilerPass;
use In2code\In2publishCore\Component\Core\DependencyInjection\DemandResolverPass;
use In2code\In2publishCore\Component\Core\DependencyInjection\PublisherPass;
use In2code\In2publishCore\Component\Core\DependencyInjection\RecordExtensionProvider\RecordExtensionsProvider;
use In2code\In2publishCore\Component\Core\DependencyInjection\RecordExtensionTraitCompilerPass;
use In2code\In2publishCore\Component\Core\DependencyInjection\TcaPreProcessorPass;
use In2code\In2publishCore\Component\Core\PreProcessing\TcaPreProcessor;
use In2code\In2publishCore\Component\Core\Publisher\Publisher;
use In2code\In2publishCore\Component\Core\Resolver\Resolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Core\DependencyInjection\PublicServicePass;

return static function (ContainerBuilder $builder): void {
    $builder->registerForAutoconfiguration(TcaPreProcessor::class)->addTag('in2publish_core.tca.preprocessor');
    $builder->registerForAutoconfiguration(Resolver::class)->addTag('in2publish_core.tca.resolver');
    $builder->registerForAutoconfiguration(Publisher::class)->addTag('in2publish_core.publisher');
    $builder->registerForAutoconfiguration(DemandResolver::class)->addTag('in2publish_core.tca.demand_resolver');
    $builder->registerForAutoconfiguration(RecordExtensionsProvider::class)
            ->addTag('in2publish_core.record.extensions_provider');

    $builder->addCompilerPass(new TcaPreProcessorPass('in2publish_core.tca.preprocessor'));
    $builder->addCompilerPass(new PublicServicePass('in2publish_core.tca.resolver', true));
    $builder->addCompilerPass(new PublisherPass('in2publish_core.publisher'));
    $builder->addCompilerPass(new DemandResolverPass('in2publish_core.tca.demand_resolver'));
    $builder->addCompilerPass(new DatabaseRecordFactoryFactoryCompilerPass('in2publish_core.factory.database_record'));
    $builder->addCompilerPass(new RecordExtensionTraitCompilerPass('in2publish_core.record.extensions_provider'));
};
