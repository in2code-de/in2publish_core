<?php

declare(strict_types=1);

use In2code\In2publishCore\Component\ConfigContainer\Definer\DefinerInterface;
use In2code\In2publishCore\Component\ConfigContainer\PostProcessor\PostProcessorInterface as PostProcessor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Core\DependencyInjection\PublicServicePass;

return static function (ContainerBuilder $builder): void {
    $builder->registerForAutoconfiguration(DefinerInterface::class)->addTag('in2publish_core.config.definer');
    $builder->registerForAutoconfiguration(PostProcessor::class)->addTag('in2publish_core.config.post_processor');

    $builder->addCompilerPass(new PublicServicePass('in2publish_core.config.definer'));
    $builder->addCompilerPass(new PublicServicePass('in2publish_core.config.post_processor'));
};
