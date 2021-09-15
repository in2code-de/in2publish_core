<?php

declare(strict_types=1);

namespace TYPO3\CMS\Core;

use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteAdapter\AdapterInterface as RemoteAdapter;
use In2code\In2publishCore\Communication\TemporaryAssetTransmission\TransmissionAdapter\AdapterInterface as TransmissionAdapter;
use In2code\In2publishCore\Config\Definer\DefinerInterface;
use In2code\In2publishCore\Config\PostProcessor\PostProcessorInterface;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Core\DependencyInjection\PublicServicePass;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->registerForAutoconfiguration(RemoteAdapter::class)
                     ->addTag('in2publish_core.adapter.remote');

    $containerBuilder->registerForAutoconfiguration(TransmissionAdapter::class)
                     ->addTag('in2publish_core.adapter.transmission');

    $containerBuilder->registerForAutoconfiguration(DefinerInterface::class)
                     ->addTag('in2publish_core.config.definer');

    $containerBuilder->registerForAutoconfiguration(PostProcessorInterface::class)
                     ->addTag('in2publish_core.config.post_processor');

    $containerBuilder->registerForAutoconfiguration(TestCaseInterface::class)
                     ->addTag('in2publish_core.testing.test');

    $containerBuilder->addCompilerPass(new PublicServicePass('in2publish_core.adapter.remote'));
    $containerBuilder->addCompilerPass(new PublicServicePass('in2publish_core.adapter.transmission'));
    $containerBuilder->addCompilerPass(new PublicServicePass('in2publish_core.config.definer'));
    $containerBuilder->addCompilerPass(new PublicServicePass('in2publish_core.config.post_processor'));
    $containerBuilder->addCompilerPass(new PublicServicePass('in2publish_core.testing.test'));
};
