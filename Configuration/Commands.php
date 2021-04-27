<?php

use In2code\In2publishCore\Command\PublishTaskRunner\RunTasksInQueueCommand;
use In2code\In2publishCore\Command\RemoteProcedureCall\ExecuteCommand;
use In2code\In2publishCore\Command\Status\AllCommand;
use In2code\In2publishCore\Command\Status\AllSitesCommand;
use In2code\In2publishCore\Command\Status\ConfigFormatTestCommand;
use In2code\In2publishCore\Command\Status\CreateMasksCommand;
use In2code\In2publishCore\Command\Status\DbConfigTestCommand;
use In2code\In2publishCore\Command\Status\DbInitQueryEncodedCommand;
use In2code\In2publishCore\Command\Status\EncryptionKeyCommand;
use In2code\In2publishCore\Command\Status\GlobalConfigurationCommand;
use In2code\In2publishCore\Command\Status\ShortSiteConfigurationCommand;
use In2code\In2publishCore\Command\Status\SiteConfigurationCommand;
use In2code\In2publishCore\Command\Status\Typo3VersionCommand;
use In2code\In2publishCore\Command\Status\VersionCommand;
use In2code\In2publishCore\Command\Table\BackupCommand;
use In2code\In2publishCore\Command\Table\ImportCommand;
use In2code\In2publishCore\Command\Table\PublishCommand;
use In2code\In2publishCore\Command\Tools\TestCommand;

return [
    // PublishTaskRunner
    RunTasksInQueueCommand::IDENTIFIER => [
        'class' => RunTasksInQueueCommand::class,
    ],
    // RemoteProcedureCall
    ExecuteCommand::IDENTIFIER => [
        'class' => ExecuteCommand::class,
    ],
    // Status
    AllCommand::IDENTIFIER => [
        'class' => AllCommand::class,
    ],
    AllSitesCommand::IDENTIFIER => [
        'class' => AllSitesCommand::class,
    ],
    ConfigFormatTestCommand::IDENTIFIER => [
        'class' => ConfigFormatTestCommand::class,
    ],
    CreateMasksCommand::IDENTIFIER => [
        'class' => CreateMasksCommand::class,
    ],
    DbConfigTestCommand::IDENTIFIER => [
        'class' => DbConfigTestCommand::class,
    ],
    DbInitQueryEncodedCommand::IDENTIFIER => [
        'class' => DbInitQueryEncodedCommand::class,
    ],
    GlobalConfigurationCommand::IDENTIFIER => [
        'class' => GlobalConfigurationCommand::class,
    ],
    EncryptionKeyCommand::IDENTIFIER => [
        'class' => EncryptionKeyCommand::class,
    ],
    ShortSiteConfigurationCommand::IDENTIFIER => [
        'class' => ShortSiteConfigurationCommand::class,
    ],
    SiteConfigurationCommand::IDENTIFIER => [
        'class' => SiteConfigurationCommand::class,
    ],
    Typo3VersionCommand::IDENTIFIER => [
        'class' => Typo3VersionCommand::class,
    ],
    VersionCommand::IDENTIFIER => [
        'class' => VersionCommand::class,
    ],
    // Table
    BackupCommand::IDENTIFIER => [
        'class' => BackupCommand::class,
    ],
    ImportCommand::IDENTIFIER => [
        'class' => ImportCommand::class,
    ],
    PublishCommand::IDENTIFIER => [
        'class' => PublishCommand::class,
    ],
    // Tools
    TestCommand::IDENTIFIER => [
        'class' => TestCommand::class,
    ],
];
