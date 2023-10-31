<?php

declare(strict_types=1);

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
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

return static function (ContainerConfigurator $configurator): void {
    $configurator->import('Component/*/ForeignServices.php');
    $configurator->import('Features/*/ForeignServices.php');

    $services = $configurator->services();
    $defaults = $services->defaults();
    $defaults->autowire();
    $defaults->autoconfigure();
    $defaults->private();
    $defaults->bind('$localDatabase', new Reference('In2code.In2publishCore.Database.Local'));
    $defaults->bind('$foreignDatabase', new Reference('In2code.In2publishCore.Database.Foreign'));

    $services->load(
        'In2code\\In2publishCore\\Command\\Foreign\\',
        __DIR__ . '/../Classes/Command/Foreign',
    );
    $services->set(AllCommand::class)
             ->tag(
                 'console.command',
                 [
                     'command' => 'in2publish_core:status:all',
                     'description' => 'Prints the configured fileCreateMask and folderCreateMask',
                     'hidden' => true,
                     'schedulable' => false,
                 ],
             );
    $services->set(AllSitesCommand::class)
             ->tag(
                 'console.command',
                 [
                     'command' => 'in2publish_core:status:allsites',
                     'description' => 'Prints all Sites serialized and encoded. Internal CLI API.',
                     'hidden' => true,
                     'schedulable' => false,
                 ],
             );
    $services->set(ConfigFormatTestCommand::class)
             ->tag(
                 'console.command',
                 [
                     'command' => 'in2publish_core:status:configformattest',
                     'description' => 'Tests the configuration on foreign for its format',
                     'hidden' => true,
                     'schedulable' => false,
                 ],
             );
    $services->set(CreateMasksCommand::class)
             ->tag(
                 'console.command',
                 [
                     'command' => 'in2publish_core:status:createmasks',
                     'description' => 'Prints the configured fileCreateMask and folderCreateMask',
                     'hidden' => true,
                     'schedulable' => false,
                 ],
             );
    $services->set(DbConfigTestCommand::class)
             ->tag(
                 'console.command',
                 [
                     'command' => 'in2publish_core:status:dbconfigtest',
                     'description' => 'Reads from the local task table and writes all found hashes for the db config test',
                     'hidden' => true,
                     'schedulable' => false,
                 ],
             );
    $services->set(DbInitQueryEncodedCommand::class)
             ->tag(
                 'console.command',
                 [
                     'command' => 'in2publish_core:status:dbinitqueryencoded',
                     'description' => 'Prints the initCommands as json and base64 encoded string',
                     'hidden' => true,
                     'schedulable' => false,
                 ],
             );
    $services->set(GlobalConfigurationCommand::class)
             ->tag(
                 'console.command',
                 [
                     'command' => 'in2publish_core:status:globalconfiguration',
                     'description' => 'Prints global configuration values',
                     'hidden' => true,
                     'schedulable' => false,
                 ],
             );
    $services->set(EncryptionKeyCommand::class)
             ->tag(
                 'console.command',
                 [
                     'command' => 'in2publish_core:status:encryptionkey',
                     'description' => 'Prints the encryption key as base64 encoded string',
                     'hidden' => true,
                     'schedulable' => false,
                 ],
             );
    $services->set(ShortSiteConfigurationCommand::class)
             ->tag(
                 'console.command',
                 [
                     'command' => 'in2publish_core:status:shortsiteconfiguration',
                     'description' => 'Prints a base64 encoded json array containing all configured sites',
                     'hidden' => true,
                     'schedulable' => false,
                 ],
             );
    $services->set(SiteConfigurationCommand::class)
             ->tag(
                 'console.command',
                 [
                     'command' => 'in2publish_core:status:siteconfiguration',
                     'description' => 'Outputs the requested Site serialized and encoded.',
                     'hidden' => true,
                     'schedulable' => false,
                 ],
             );
    $services->set(Typo3VersionCommand::class)
             ->tag(
                 'console.command',
                 [
                     'command' => 'in2publish_core:status:typo3version',
                     'description' => 'Prints TYPO3 version',
                     'hidden' => true,
                     'schedulable' => false,
                 ],
             );
    $services->set(VersionCommand::class)
             ->tag(
                 'console.command',
                 [
                     'command' => 'in2publish_core:status:version',
                     'description' => 'Prints the version number of the currently installed in2publish_core extension',
                     'hidden' => true,
                     'schedulable' => false,
                 ],
             );
};
