<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Configuration;

/**
 * @codeCoverageIgnore
 */
trait PageTypeServiceInjection
{
    protected PageTypeService $pageTypeService;

    /**
     * @noinspection PhpUnused
     */
    public function injectPageTypeService(PageTypeService $pageTypeService): void
    {
        $this->pageTypeService = $pageTypeService;
    }
}
