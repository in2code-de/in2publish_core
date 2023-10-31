<?php

declare(strict_types=1);

use In2code\In2publishCore\Component\TemporaryAssetTransmission\DependencyInjection\RegisterTransmissionAdapterCompilerPass;
use In2code\In2publishCore\Component\TemporaryAssetTransmission\TransmissionAdapter\AdapterInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Core\DependencyInjection\PublicServicePass;

return static function (ContainerBuilder $builder): void {
    $builder->registerForAutoconfiguration(AdapterInterface::class)->addTag('in2publish_core.adapter.transmission');
    $builder->addCompilerPass(new PublicServicePass('in2publish_core.adapter.transmission'));

    $builder->addCompilerPass(
        new RegisterTransmissionAdapterCompilerPass('in2publish_core.adapter.transmission_adapter'),
    );
};
