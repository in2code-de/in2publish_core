<?php

/**
 * Defined starting with TYPO3 v12
 * @noinspection PhpUndefinedClassInspection
 */

declare(strict_types=1);

namespace In2code\In2publishCore\CommonInjection;

use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;

/**
 * @codeCoverageIgnore
 */
trait PageDoktypeRegistryInjection
{
    protected PageDoktypeRegistry $pageDoktypeRegistry;

    /**
     * @noinspection PhpUnused
     */
    public function injectPageDoktypeRegistry(PageDoktypeRegistry $pageDoktypeRegistry): void
    {
        $this->pageDoktypeRegistry = $pageDoktypeRegistry;
    }
}
