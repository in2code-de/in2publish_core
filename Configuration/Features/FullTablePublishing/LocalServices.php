<?php

declare(strict_types=1);

use In2code\In2publishCore\Features\FullTablePublishing\Command\Local\ImportCommand;
use In2code\In2publishCore\Features\FullTablePublishing\Command\Local\PublishCommand;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();
    $defaults = $services->defaults();
    $defaults->autowire();
    $defaults->autoconfigure();
    $defaults->private();
    $defaults->bind('$localDatabase', new Reference('In2code.In2publishCore.Database.Local'));
    $defaults->bind('$foreignDatabase', new Reference('In2code.In2publishCore.Database.Foreign'));

    $services->load(
        'In2code\\In2publishCore\\Features\\FullTablePublishing\\Command\\Local\\',
        __DIR__ . '/../../../Classes/Features/FullTablePublishing/Command/Local/'
    );
    $services->set(ImportCommand::class)
             ->tag(
                 'console.command',
                 [
                     'command' => ImportCommand::IDENTIFIER,
                     'description' => 'Truncates the local table and writes the data from the foreign table into it.',
                 ]
             );
    $services->set(PublishCommand::class)
             ->tag(
                 'console.command',
                 [
                     'command' => PublishCommand::IDENTIFIER,
                     'description' => 'Truncates the foreign table and writes the data from the local table into it.',
                 ]
             );
};
