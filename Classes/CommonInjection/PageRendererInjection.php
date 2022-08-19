<?php

declare(strict_types=1);

namespace In2code\In2publishCore\CommonInjection;

use TYPO3\CMS\Core\Page\PageRenderer;

/**
 * @codeCoverageIgnore
 */
trait PageRendererInjection
{
    protected PageRenderer $pageRenderer;

    /**
     * @noinspection PhpUnused
     */
    public function injectPageRenderer(PageRenderer $pageRenderer): void
    {
        $this->pageRenderer = $pageRenderer;
    }
}
