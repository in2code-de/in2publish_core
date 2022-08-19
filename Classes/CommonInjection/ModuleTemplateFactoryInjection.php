<?php

declare(strict_types=1);

namespace In2code\In2publishCore\CommonInjection;

use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;

/**
 * @codeCoverageIgnore
 */
trait ModuleTemplateFactoryInjection
{
    protected ModuleTemplateFactory $moduleTemplateFactory;

    /**
     * @noinspection PhpUnused
     */
    public function injectModuleTemplateFactory(ModuleTemplateFactory $moduleTemplateFactory): void
    {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }
}
