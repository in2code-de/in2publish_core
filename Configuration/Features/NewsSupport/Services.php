<?php

declare(strict_types=1);

use In2code\In2publishCore\Event\RecordWasPublished;
use In2code\In2publishCore\Event\RecursiveRecordPublishingEnded;
use In2code\In2publishCore\Features\NewsSupport\Domain\Anomaly\NewsCacheInvalidator;
use In2code\In2publishCore\Utility\ExtensionUtility;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();
    $defaults = $services->defaults();
    $defaults->autowire();
    $defaults->autoconfigure();
    $defaults->private();

    if (ExtensionUtility::isLoaded('news')) {
        $services->set('tx_in2publish_newssupport_event_listener')
                 ->class(NewsCacheInvalidator::class)
                 ->tag(
                     'event.listener',
                     [
                         'identifier' => 'in2publishcore-NewsCacheInvalidator-RecordWasPublished',
                         'method' => 'registerClearCacheTasks',
                         'event' => RecordWasPublished::class,
                     ],
                 )
                 ->tag(
                     'event.listener',
                     [
                         'identifier' => 'in2publishcore-NewsCacheInvalidator-RecursiveRecordPublishingEnded',
                         'method' => 'writeClearCacheTask',
                         'event' => RecursiveRecordPublishingEnded::class,
                     ],
                 );
    }
};
