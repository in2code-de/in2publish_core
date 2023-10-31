<?php

declare(strict_types=1);

use In2code\In2publishCore\Component\PostPublishTaskExecution\Command\Foreign\RunTasksInQueueCommand;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();
    $defaults = $services->defaults();
    $defaults->autowire(true);
    $defaults->autoconfigure(true);
    $defaults->private();

    $services->load(
        'In2code\\In2publishCore\\Component\\PostPublishTaskExecution\\Command\\Foreign\\',
        __DIR__ . '/../../../Classes/Component/PostPublishTaskExecution/Command/Foreign/',
    );
    $services->set(RunTasksInQueueCommand::class)
             ->tag(
                 'console.command',
                 [
                     'command' => 'in2publish_core:publishtasksrunner:runtasksinqueue',
                     'description' => 'Reads all Tasks to execute from the Database and executes them one after another. The success of a Task is echoed to the console or scheduler backend module, including any error message of failed tasks. NOTE: This command is used for internal operations in in2publish_core',
                     'hidden' => true,
                     'schedulable' => false,
                 ],
             );
};
