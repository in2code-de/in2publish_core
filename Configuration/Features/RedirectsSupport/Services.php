<?php

declare(strict_types=1);

use In2code\In2publishCore\Event\CollectReasonsWhyTheRecordIsNotPublishable;
use In2code\In2publishCore\Event\RecordRelationsWereResolved;
use In2code\In2publishCore\Event\RecordWasPublished;
use In2code\In2publishCore\Event\RecordWasSelectedForPublishing;
use In2code\In2publishCore\Event\RecursiveRecordPublishingEnded;
use In2code\In2publishCore\Features\RedirectsSupport\DataBender\RedirectSourceHostReplacement;
use In2code\In2publishCore\Features\RedirectsSupport\Domain\Anomaly\RedirectCacheUpdater;
use In2code\In2publishCore\Features\RedirectsSupport\EventListener\EarlyRedirectsSupportEventListener;
use In2code\In2publishCore\Features\RedirectsSupport\EventListener\NoChangedPropsEventListener;
use In2code\In2publishCore\Features\RedirectsSupport\PageRecordRedirectEnhancer;
use In2code\In2publishCore\Utility\ExtensionUtility;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Core\Database\Event\AlterTableDefinitionStatementsEvent;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();
    $defaults = $services->defaults();
    $defaults->autowire();
    $defaults->autoconfigure();
    $defaults->private();

    if (ExtensionUtility::isLoaded('redirects')) {
        $services->set('tx_in2publish_redirectssupport_event_listener_schema')
                 ->class(EarlyRedirectsSupportEventListener::class)
                 ->tag(
                     'event.listener',
                     [
                         'identifier' => 'in2publishcore-EarlyRedirectsSupportEventListener-AlterTableDefinitionStatementsEvent',
                         'method' => 'onAlterTableDefinitionStatementsEvent',
                         'event' => AlterTableDefinitionStatementsEvent::class,
                     ],
                 );
        $services->set('tx_in2publish_redirectssupport_event_listener_enhancer')
                 ->class(PageRecordRedirectEnhancer::class)
                 ->tag(
                     'in2publish_core.conditional.event.listener',
                     [
                         'condition' => 'CONF:features.redirectsSupport.enable',
                         'identifier' => 'in2publishcore-PageRecordRedirectEnhancer-RecordRelationsWereResolved',
                         'method' => 'addRedirectsToPageRecord',
                         'event' => RecordRelationsWereResolved::class,
                     ],
                 );
        $services->set('tx_in2publish_redirectssupport_event_listener_no_changed_props')
                 ->class(NoChangedPropsEventListener::class)
                 ->tag(
                'in2publish_core.conditional.event.listener',
                    [
                        'condition' => 'CONF:features.redirectsSupport.enable',
                        'identifier' => 'in2publishcore-NoChangedPropsEventListener-checkChangedProps',
                        'method' => 'checkForEmptyChangedProps',
                        'event' => CollectReasonsWhyTheRecordIsNotPublishable::class,
                    ],
            );

        $services->set('tx_in2publish_redirectssupport_event_listener_replacer')
                 ->class(RedirectSourceHostReplacement::class)
                 ->tag(
                     'in2publish_core.conditional.event.listener',
                     [
                         'condition' => 'CONF:features.redirectsSupport.enable',
                         'identifier' => 'in2publishcore-RedirectSourceHostReplacement-RecordWasPublished',
                         'method' => 'replaceLocalWithForeignSourceHost',
                         'event' => RecordWasSelectedForPublishing::class,
                     ],
                 );
        $services->set('tx_in2publish_redirectssupport_event_listener_updater')
                 ->class(RedirectCacheUpdater::class)
                 ->tag(
                     'in2publish_core.conditional.event.listener',
                     [
                         'condition' => 'CONF:features.redirectsSupport.enable',
                         'identifier' => 'in2publishcore-RedirectCacheUpdater-RecordWasPublished',
                         'method' => 'publishRecordRecursiveAfterPublishing',
                         'event' => RecordWasPublished::class,
                     ],
                 )
                 ->tag(
                     'in2publish_core.conditional.event.listener',
                     [
                         'condition' => 'CONF:features.redirectsSupport.enable',
                         'identifier' => 'in2publishcore-RedirectCacheUpdater-RecursiveRecordPublishingEnded',
                         'method' => 'publishRecordRecursiveEnd',
                         'event' => RecursiveRecordPublishingEnded::class,
                     ],
                 );
    }
};
