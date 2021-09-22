<?php

declare(strict_types=1);

namespace TYPO3\CMS\Core;

use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteAdapter\AdapterInterface as RemoteAdapter;
use In2code\In2publishCore\Communication\TemporaryAssetTransmission\TransmissionAdapter\AdapterInterface as TransmissionAdapter;
use In2code\In2publishCore\Config\Definer\DefinerInterface;
use In2code\In2publishCore\Config\PostProcessor\PostProcessorInterface as PostProcessor;
use In2code\In2publishCore\Event\AllRelatedRecordsWereAddedToOneRecord;
use In2code\In2publishCore\Event\PublishingOfOneRecordBegan;
use In2code\In2publishCore\Event\PublishingOfOneRecordEnded;
use In2code\In2publishCore\Event\RecursiveRecordPublishingEnded;
use In2code\In2publishCore\Features\NewsSupport\Domain\Anomaly\NewsCacheInvalidator;
use In2code\In2publishCore\Features\RedirectsSupport\DataBender\RedirectSourceHostReplacement;
use In2code\In2publishCore\Features\RedirectsSupport\Domain\Anomaly\RedirectCacheUpdater;
use In2code\In2publishCore\Features\RedirectsSupport\EventListener\EarlyRedirectsSupportEventListener;
use In2code\In2publishCore\Features\RedirectsSupport\PageRecordRedirectEnhancer;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Core\Database\Event\AlterTableDefinitionStatementsEvent;
use TYPO3\CMS\Core\DependencyInjection\PublicServicePass;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

return static function (ContainerConfigurator $configurator, ContainerBuilder $builder) {
    $builder->registerForAutoconfiguration(RemoteAdapter::class)->addTag('in2publish_core.adapter.remote');
    $builder->registerForAutoconfiguration(TransmissionAdapter::class)->addTag('in2publish_core.adapter.transmission');
    $builder->registerForAutoconfiguration(DefinerInterface::class)->addTag('in2publish_core.config.definer');
    $builder->registerForAutoconfiguration(PostProcessor::class)->addTag('in2publish_core.config.post_processor');
    $builder->registerForAutoconfiguration(TestCaseInterface::class)->addTag('in2publish_core.testing.test');

    $builder->addCompilerPass(new PublicServicePass('in2publish_core.adapter.remote'));
    $builder->addCompilerPass(new PublicServicePass('in2publish_core.adapter.transmission'));
    $builder->addCompilerPass(new PublicServicePass('in2publish_core.config.definer'));
    $builder->addCompilerPass(new PublicServicePass('in2publish_core.config.post_processor'));
    $builder->addCompilerPass(new PublicServicePass('in2publish_core.testing.test'));

    $services = $configurator->services();
    $defaults = $services->defaults();
    $defaults->autowire(true);
    $defaults->autoconfigure(true);
    $defaults->private();
    $defaults->bind('$localDatabase', new Reference('In2code.In2publishCore.Database.Local'));
    $defaults->bind('$foreignDatabase', new Reference('In2code.In2publishCore.Database.Foreign'));

    if (ExtensionManagementUtility::isLoaded('news')) {
        $services->set('tx_in2publish_newssupport_event_listener')
                 ->class(NewsCacheInvalidator::class)
                 ->tag(
                     'event.listener',
                     [
                         'identifier' => 'in2publishcore-NewsCacheInvalidator-PublishingOfOneRecordBegan',
                         'method' => 'registerClearCacheTasks',
                         'event' => PublishingOfOneRecordBegan::class,
                     ]
                 )
                 ->tag(
                     'event.listener',
                     [
                         'identifier' => 'in2publishcore-NewsCacheInvalidator-RecursiveRecordPublishingEnded',
                         'method' => 'writeClearCacheTask',
                         'event' => RecursiveRecordPublishingEnded::class,
                     ]
                 );
    }

    if (ExtensionManagementUtility::isLoaded('redirects')) {
        $services->set('tx_in2publish_redirectssupport_event_listener_schema')
                 ->class(EarlyRedirectsSupportEventListener::class)
                 ->tag(
                     'event.listener',
                     [
                         'identifier' => 'in2publishcore-EarlyRedirectsSupportEventListener-AlterTableDefinitionStatementsEvent',
                         'method' => 'onAlterTableDefinitionStatementsEvent',
                         'event' => AlterTableDefinitionStatementsEvent::class,
                     ]
                 );
        $services->set('tx_in2publish_redirectssupport_event_listener_enhancer')
                 ->class(PageRecordRedirectEnhancer::class)
                 ->tag(
                     'event.listener',
                     [
                         'identifier' => 'in2publishcore-PageRecordRedirectEnhancer-AllRelatedRecordsWereAddedToOneRecord',
                         'method' => 'addRedirectsToPageRecord',
                         'event' => AllRelatedRecordsWereAddedToOneRecord::class,
                     ]
                 );
        $services->set('tx_in2publish_redirectssupport_event_listener_replacer')
                 ->class(RedirectSourceHostReplacement::class)
                 ->tag(
                     'event.listener',
                     [
                         'identifier' => 'in2publishcore-RedirectSourceHostReplacement-PublishingOfOneRecordBegan',
                         'method' => 'replaceLocalWithForeignSourceHost',
                         'event' => PublishingOfOneRecordBegan::class,
                     ]
                 );
        $services->set('tx_in2publish_redirectssupport_event_listener_updater')
                 ->class(RedirectCacheUpdater::class)
                 ->tag(
                     'event.listener',
                     [
                         'identifier' => 'in2publishcore-RedirectCacheUpdater-PublishingOfOneRecordEnded',
                         'method' => 'publishRecordRecursiveAfterPublishing',
                         'event' => PublishingOfOneRecordEnded::class,
                     ]
                 )
                 ->tag(
                     'event.listener',
                     [
                         'identifier' => 'in2publishcore-RedirectCacheUpdater-PublishingOfOneRecordEnded',
                         'method' => 'publishRecordRecursiveEnd',
                         'event' => RecursiveRecordPublishingEnded::class,
                     ]
                 );
    }
};
