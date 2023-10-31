<?php

declare(strict_types=1);

use In2code\In2publishCore\Component\RemoteCommandExecution\DependencyInjection\RegisterRemoteAdapterCompilerPass;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteAdapter\AdapterInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Core\DependencyInjection\PublicServicePass;

return static function (ContainerBuilder $builder): void {
    $builder->registerForAutoconfiguration(AdapterInterface::class)->addTag('in2publish_core.adapter.remote');
    $builder->addCompilerPass(new PublicServicePass('in2publish_core.adapter.remote'));

    $builder->addCompilerPass(
        new RegisterRemoteAdapterCompilerPass('in2publish_core.adapter.remote_adapter'),
    );
};
