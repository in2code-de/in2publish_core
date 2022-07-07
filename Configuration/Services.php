<?php

declare(strict_types=1);

use In2code\In2publishCore\Config\Definer\DefinerInterface;
use In2code\In2publishCore\Config\PostProcessor\PostProcessorInterface as PostProcessor;
use In2code\In2publishCore\DependencyInjection\DatabaseRecordFactoryFactoryCompilerPass;
use In2code\In2publishCore\Domain\Factory\DatabaseRecordFactory;
use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Core\DependencyInjection\PublicServicePass;
use TYPO3\CMS\Core\Utility\GeneralUtility;

return static function (ContainerConfigurator $configurator, ContainerBuilder $builder): void {
    $configurator->import('Component/*/Services.php');
    $configurator->import('Features/*/Services.php');

    if (GeneralUtility::makeInstance(ContextService::class)->isLocal()) {
        $configurator->import('LocalServices.php');
    } else {
        $configurator->import('ForeignServices.php');
    }

    $builder->registerForAutoconfiguration(DefinerInterface::class)->addTag('in2publish_core.config.definer');
    $builder->registerForAutoconfiguration(PostProcessor::class)->addTag('in2publish_core.config.post_processor');
    $builder->registerForAutoconfiguration(TestCaseInterface::class)->addTag('in2publish_core.testing.test');
    $builder->registerForAutoconfiguration(DatabaseRecordFactory::class)->addTag(
        'in2publish_core.factory.database_record'
    );

    $builder->addCompilerPass(new PublicServicePass('in2publish_core.config.definer'));
    $builder->addCompilerPass(new PublicServicePass('in2publish_core.config.post_processor'));
    $builder->addCompilerPass(new PublicServicePass('in2publish_core.testing.test'));
    $builder->addCompilerPass(new DatabaseRecordFactoryFactoryCompilerPass('in2publish_core.factory.database_record'));
};
