<?php

declare(strict_types=1);

namespace In2code\In2publishCore\CommonInjection;

use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;

/**
 * @codeCoverageIgnore
 */
trait FlexFormToolsInjection
{
    protected FlexFormTools $flexFormTools;

    /**
     * @noinspection PhpUnused
     */
    public function injectFlexFormTools(FlexFormTools $flexFormTools): void
    {
        $this->flexFormTools = $flexFormTools;
    }
}
