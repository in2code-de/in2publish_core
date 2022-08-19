<?php

declare(strict_types=1);

namespace In2code\In2publishCore\CommonInjection;

use TYPO3\CMS\Core\Imaging\IconFactory;

/**
 * @codeCoverageIgnore
 */
trait IconFactoryInjection
{
    protected IconFactory $iconFactory;

    /**
     * @noinspection PhpUnused
     */
    public function injectIconFactory(IconFactory $iconFactory): void
    {
        $this->iconFactory = $iconFactory;
    }
}
