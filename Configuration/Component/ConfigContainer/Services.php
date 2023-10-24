<?php

declare(strict_types=1);

use In2code\In2publishCore\Component\ConfigContainer\Definer\DefinerInterface;
use In2code\In2publishCore\Component\ConfigContainer\Definer\DefinerServiceInterface;
use In2code\In2publishCore\Component\ConfigContainer\DependencyInjection\ConfigContainerServicesCompilerPass;
use In2code\In2publishCore\Component\ConfigContainer\Migration\MigrationServiceInterface;
use In2code\In2publishCore\Component\ConfigContainer\PostProcessor\PostProcessorInterface as PostProcessor;
use In2code\In2publishCore\Component\ConfigContainer\PostProcessor\PostProcessorServiceInterface;
use In2code\In2publishCore\Component\ConfigContainer\Provider\ProviderServiceInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Core\DependencyInjection\PublicServicePass;

return static function (ContainerBuilder $builder): void {
    $builder->registerForAutoconfiguration(DefinerInterface::class)->addTag('in2publish_core.config.definer');
    $builder->registerForAutoconfiguration(PostProcessor::class)->addTag('in2publish_core.config.post_processor');

    $builder->addCompilerPass(new PublicServicePass('in2publish_core.config.definer'));
    $builder->addCompilerPass(new PublicServicePass('in2publish_core.config.post_processor'));

    $builder->registerForAutoconfiguration(DefinerServiceInterface::class)
            ->addTag('in2publish_core.config.definer_service');
    $builder->registerForAutoconfiguration(MigrationServiceInterface::class)
            ->addTag('in2publish_core.config.migration_service');
    $builder->registerForAutoconfiguration(PostProcessorServiceInterface::class)
            ->addTag('in2publish_core.config.postProcessor_service');
    $builder->registerForAutoconfiguration(ProviderServiceInterface::class)
            ->addTag('in2publish_core.config.provider_service');

    $builder->addCompilerPass(
        new ConfigContainerServicesCompilerPass(
            'in2publish_core.config.definer_service',
            'in2publish_core.config.migration_service',
            'in2publish_core.config.postProcessor_service',
            'in2publish_core.config.provider_service',
        ),
    );
};
