<?php

declare(strict_types=1);

use In2code\In2publishCore\Component\FalHandling\FalFinder;
use In2code\In2publishCore\Component\FalHandling\PostProcessing\PostProcessor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Core\DependencyInjection\PublicServicePass;

return static function (ContainerConfigurator $configurator, ContainerBuilder $builder) {
    $builder->registerForAutoconfiguration(FalFinder::class)->addTag('in2publish_core.fal.finder');
    $builder->registerForAutoconfiguration(PostProcessor::class)->addTag('in2publish_core.fal.post_processor');

    $builder->addCompilerPass(new PublicServicePass('in2publish_core.fal.finder'));
    $builder->addCompilerPass(new PublicServicePass('in2publish_core.fal.post_processor'));
};
