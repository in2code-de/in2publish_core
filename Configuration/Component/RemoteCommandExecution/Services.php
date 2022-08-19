<?php

declare(strict_types=1);

use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteAdapter\AdapterInterface as RemoteAdapter;
use In2code\In2publishCore\Component\TemporaryAssetTransmission\TransmissionAdapter\AdapterInterface as TransmissionAdapter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Core\DependencyInjection\PublicServicePass;

return static function (ContainerBuilder $builder): void {
    $builder->registerForAutoconfiguration(RemoteAdapter::class)->addTag('in2publish_core.adapter.remote');
    $builder->registerForAutoconfiguration(TransmissionAdapter::class)->addTag('in2publish_core.adapter.transmission');

    $builder->addCompilerPass(new PublicServicePass('in2publish_core.adapter.remote'));
    $builder->addCompilerPass(new PublicServicePass('in2publish_core.adapter.transmission'));
};
