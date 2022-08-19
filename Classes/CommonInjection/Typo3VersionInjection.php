<?php

declare(strict_types=1);

namespace In2code\In2publishCore\CommonInjection;

use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * @codeCoverageIgnore
 */
trait Typo3VersionInjection
{
    private Typo3Version $typo3Version;

    /**
     * @noinspection PhpUnused
     */
    public function injectTypo3Version(Typo3Version $typo3Version): void
    {
        $this->typo3Version = $typo3Version;
    }
}
