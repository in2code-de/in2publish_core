<?php

declare(strict_types=1);

use In2code\In2publishCore\Features\SystemInformationExport\DependencyInjection\SystemInformationExporterCompilerPass;
use In2code\In2publishCore\Features\SystemInformationExport\Exporter\SystemInformationExporter;
use Symfony\Component\DependencyInjection\ContainerBuilder;

return static function (ContainerBuilder $builder): void {
    $builder->registerForAutoconfiguration(SystemInformationExporter::class)
            ->addTag('in2publish_core.system_information_exporter');

    $builder->addCompilerPass(new SystemInformationExporterCompilerPass('in2publish_core.system_information_exporter'));
};
