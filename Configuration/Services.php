<?php

declare(strict_types=1);

use In2code\In2publishCore\Component\Core\Record\Factory\DatabaseRecordFactory;
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

    $builder->registerForAutoconfiguration(TestCaseInterface::class)->addTag('in2publish_core.testing.test');
    $builder->registerForAutoconfiguration(DatabaseRecordFactory::class)->addTag(
        'in2publish_core.factory.database_record'
    );

    $builder->addCompilerPass(new PublicServicePass('in2publish_core.testing.test'));
};
