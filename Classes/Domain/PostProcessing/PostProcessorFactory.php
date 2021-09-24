<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\PostProcessing;

use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Domain\PostProcessing\Processor\PostProcessor;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PostProcessorFactory
{
    /** @var ConfigContainer */
    protected $configContainer;

    public function __construct(ConfigContainer $configContainer)
    {
        $this->configContainer = $configContainer;
    }

    public function createPostProcessor(): PostProcessor
    {
        if ($this->configContainer->get('factory.fal.reserveSysFileUids')) {
            return GeneralUtility::makeInstance(Processor\FileIndexPostProcessor::class);
        }
        return GeneralUtility::makeInstance(Processor\FalIndexPostProcessor::class);
    }
}
