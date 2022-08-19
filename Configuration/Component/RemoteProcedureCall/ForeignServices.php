<?php

declare(strict_types=1);

use In2code\In2publishCore\Component\RemoteProcedureCall\Command\Foreign\ExecuteCommand;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();
    $defaults = $services->defaults();
    $defaults->autowire(true);
    $defaults->autoconfigure(true);
    $defaults->private();

    $services->load(
        'In2code\\In2publishCore\\Component\\RemoteProcedureCall\\Command\\Foreign\\',
        __DIR__ . '/../../../Classes/Component/RemoteProcedureCall/Command/Foreign/'
    );
    $services->set(ExecuteCommand::class)
             ->tag(
                 'console.command',
                 [
                     'command' => ExecuteCommand::IDENTIFIER,
                     'description' => 'Receives an envelope and executes the contained command',
                     'schedulable' => false,
                 ]
             );
};
