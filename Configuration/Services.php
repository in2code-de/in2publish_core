<?php

declare(strict_types=1);

use In2code\In2publishCore\Component\Core\Record\Factory\DatabaseRecordFactory;
use In2code\In2publishCore\Service\Configuration\LegacyPageTypeService;
use In2code\In2publishCore\Service\Configuration\PageTypeRegistryService;
use In2code\In2publishCore\Service\Configuration\PageTypeService;
use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Core\DependencyInjection\PublicServicePass;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use const In2code\In2publishCore\TYPO3_V11;

return static function (ContainerConfigurator $configurator, ContainerBuilder $builder): void {
    $configurator->import('Component/*/Services.php');
    $configurator->import('Features/*/Services.php');

    if (GeneralUtility::makeInstance(ContextService::class)->isLocal()) {
        $configurator->import('LocalServices.php');
    } else {
        $configurator->import('ForeignServices.php');
    }

    if ($builder->hasDefinition(PageTypeService::class)) {
        $pageTypeServiceDefinition = $builder->hasDefinition(PageTypeService::class);
    } else {
        $pageTypeServiceDefinition = new Definition(PageTypeService::class);
    }
    $pageTypeServiceDefinition->setAutoconfigured(true);
    $pageTypeServiceDefinition->setAutowired(true);
    $pageTypeServiceDefinition->setShared(true);
    $pageTypeServiceDefinition->setPublic(true);

    if (TYPO3_V11) {
        $pageTypeServiceDefinition->setClass(LegacyPageTypeService::class);
    } else {
        $pageTypeServiceDefinition->setClass(PageTypeRegistryService::class);
    }
    $builder->setDefinition(PageTypeService::class, $pageTypeServiceDefinition);

    $builder->registerForAutoconfiguration(TestCaseInterface::class)
            ->addTag('in2publish_core.testing.test');
    $builder->registerForAutoconfiguration(DatabaseRecordFactory::class)
            ->addTag('in2publish_core.factory.database_record',);

    $builder->addCompilerPass(new PublicServicePass('in2publish_core.testing.test'));
};
