<?php

declare(strict_types=1);

use In2code\In2publishCore\Command\Foreign\RemoteProcedureCall\ExecuteCommand;
use In2code\In2publishCore\Command\Foreign\Status\AllCommand;
use In2code\In2publishCore\Command\Foreign\Status\AllSitesCommand;
use In2code\In2publishCore\Command\Foreign\Status\ConfigFormatTestCommand;
use In2code\In2publishCore\Command\Foreign\Status\CreateMasksCommand;
use In2code\In2publishCore\Command\Foreign\Status\DbConfigTestCommand;
use In2code\In2publishCore\Command\Foreign\Status\DbInitQueryEncodedCommand;
use In2code\In2publishCore\Command\Foreign\Status\EncryptionKeyCommand;
use In2code\In2publishCore\Command\Foreign\Status\GlobalConfigurationCommand;
use In2code\In2publishCore\Command\Foreign\Status\ShortSiteConfigurationCommand;
use In2code\In2publishCore\Command\Foreign\Status\SiteConfigurationCommand;
use In2code\In2publishCore\Command\Foreign\Status\Typo3VersionCommand;
use In2code\In2publishCore\Command\Foreign\Status\VersionCommand;
use In2code\In2publishCore\Command\Local\Table\BackupCommand;
use In2code\In2publishCore\Command\Local\Table\ImportCommand;
use In2code\In2publishCore\Command\Local\Table\PublishCommand;
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
use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Utility\ExtensionUtility;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Core\Database\Event\AlterTableDefinitionStatementsEvent;
use TYPO3\CMS\Core\DependencyInjection\PublicServicePass;
use TYPO3\CMS\Core\Utility\GeneralUtility;

return static function (ContainerConfigurator $configurator, ContainerBuilder $builder) {
    (include __DIR__ . '/Component/FalHandling/Services.php')($configurator, $builder);
    (include __DIR__ . '/Component/PostPublishTaskExecution/Services.php')($configurator, $builder);
    (include __DIR__ . '/Component/RecordHandling/Services.php')($configurator, $builder);
    (include __DIR__ . '/Component/TcaHandling/Services.php')($configurator, $builder);
    (include __DIR__ . '/Features/AdminTools/Services.php')($configurator, $builder);
    (include __DIR__ . '/Features/LogsIntegration/Services.php')($configurator, $builder);
    (include __DIR__ . '/Features/SystemInformationExport/Services.php')($configurator, $builder);

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

    if (GeneralUtility::makeInstance(ContextService::class)->isLocal()) {
        $services->load(
            'In2code\\In2publishCore\\Command\\Local\\',
            __DIR__ . '/../Classes/Command/Local'
        );
        $services->set(BackupCommand::class)
                 ->tag(
                     'console.command',
                     [
                         'command' => 'in2publish_core:table:backup',
                         'description' => 'Stores a backup of the complete local table into the configured directory',
                     ]
                 );
        $services->set(ImportCommand::class)
                 ->tag(
                     'console.command',
                     [
                         'command' => 'in2publish_core:table:import',
                         'description' => 'Stores a backup of the complete local table into the configured directory',
                     ]
                 );
        $services->set(PublishCommand::class)
                 ->tag(
                     'console.command',
                     [
                         'command' => 'in2publish_core:table:publish',
                         'description' => 'Copies a complete table from stage to production and overwrites all old entries!',
                     ]
                 );
    } else {
        $services->load(
            'In2code\\In2publishCore\\Command\\Foreign\\',
            __DIR__ . '/../Classes/Command/Foreign'
        );
        $services->set(ExecuteCommand::class)
                 ->tag(
                     'console.command',
                     [
                         'command' => 'in2publish_core:rpc:execute',
                         'description' => 'Receives an envelope and executes the contained command',
                         'hidden' => true,
                         'schedulable' => false,
                     ]
                 );
        $services->set(AllCommand::class)
                 ->tag(
                     'console.command',
                     [
                         'command' => 'in2publish_core:status:all',
                         'description' => 'Prints the configured fileCreateMask and folderCreateMask',
                         'hidden' => true,
                         'schedulable' => false,
                     ]
                 );
        $services->set(AllSitesCommand::class)
                 ->tag(
                     'console.command',
                     [
                         'command' => 'in2publish_core:status:allsites',
                         'description' => 'Prints all Sites serialized and encoded. Internal CLI API.',
                         'hidden' => true,
                         'schedulable' => false,
                     ]
                 );
        $services->set(ConfigFormatTestCommand::class)
                 ->tag(
                     'console.command',
                     [
                         'command' => 'in2publish_core:status:configformattest',
                         'description' => 'Tests the configuration on foreign for its format',
                         'hidden' => true,
                         'schedulable' => false,
                     ]
                 );
        $services->set(CreateMasksCommand::class)
                 ->tag(
                     'console.command',
                     [
                         'command' => 'in2publish_core:status:createmasks',
                         'description' => 'Prints the configured fileCreateMask and folderCreateMask',
                         'hidden' => true,
                         'schedulable' => false,
                     ]
                 );
        $services->set(DbConfigTestCommand::class)
                 ->tag(
                     'console.command',
                     [
                         'command' => 'in2publish_core:status:dbconfigtest',
                         'description' => 'Reads from the local task table and writes all found hashes for the db config test',
                         'hidden' => true,
                         'schedulable' => false,
                     ]
                 );
        $services->set(DbInitQueryEncodedCommand::class)
                 ->tag(
                     'console.command',
                     [
                         'command' => 'in2publish_core:status:dbinitqueryencoded',
                         'description' => 'Prints the initCommands as json and base64 encoded string',
                         'hidden' => true,
                         'schedulable' => false,
                     ]
                 );
        $services->set(GlobalConfigurationCommand::class)
                 ->tag(
                     'console.command',
                     [
                         'command' => 'in2publish_core:status:globalconfiguration',
                         'description' => 'Prints global configuration values',
                         'hidden' => true,
                         'schedulable' => false,
                     ]
                 );
        $services->set(EncryptionKeyCommand::class)
                 ->tag(
                     'console.command',
                     [
                         'command' => 'in2publish_core:status:encryptionkey',
                         'description' => 'Prints the encryption key as base64 encoded string',
                         'hidden' => true,
                         'schedulable' => false,
                     ]
                 );
        $services->set(ShortSiteConfigurationCommand::class)
                 ->tag(
                     'console.command',
                     [
                         'command' => 'in2publish_core:status:shortsiteconfiguration',
                         'description' => 'Prints a base64 encoded json array containing all configured sites',
                         'hidden' => true,
                         'schedulable' => false,
                     ]
                 );
        $services->set(SiteConfigurationCommand::class)
                 ->tag(
                     'console.command',
                     [
                         'command' => 'in2publish_core:status:siteconfiguration',
                         'description' => 'Outputs the requested Site serialized and encoded.',
                         'hidden' => true,
                         'schedulable' => false,
                     ]
                 );
        $services->set(Typo3VersionCommand::class)
                 ->tag(
                     'console.command',
                     [
                         'command' => 'in2publish_core:status:typo3version',
                         'description' => 'Prints TYPO3 version',
                         'hidden' => true,
                         'schedulable' => false,
                     ]
                 );
        $services->set(VersionCommand::class)
                 ->tag(
                     'console.command',
                     [
                         'command' => 'in2publish_core:status:version',
                         'description' => 'Prints the version number of the currently installed in2publish_core extension',
                         'hidden' => true,
                         'schedulable' => false,
                     ]
                 );
    }

    if (ExtensionUtility::isLoaded('news')) {
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

    if (ExtensionUtility::isLoaded('redirects')) {
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
