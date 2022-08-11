<?php

declare(strict_types=1);

namespace In2code\In2publishCore\CommonInjection;

use TYPO3\CMS\Core\Service\FlexFormService;

/**
 * @codeCoverageIgnore
 */
trait FlexFormServiceInjection
{
    protected FlexFormService $flexFormService;

    /**
     * @noinspection PhpUnused
     */
    public function injectFlexFormService(FlexFormService $flexFormService): void
    {
        $this->flexFormService = $flexFormService;
    }
}
