<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

return static function (ContainerConfigurator $configurator) {
    if (ExtensionManagementUtility::isLoaded('logs')) {
        $services = $configurator->services();
        $defaults = $services->defaults();
        $defaults->autowire(true);
        $defaults->autoconfigure(true);
        $defaults->private();

        $services->load(
            'In2code\\In2publishCore\\Features\\LogsIntegration\\',
            __DIR__ . '/../../../Classes/Features/LogsIntegration/*'
        );
    }
};
