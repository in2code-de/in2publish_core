<?php

declare(strict_types=1);

namespace In2code\In2publishCore\CommonInjection;

use TYPO3\CMS\Core\Registry;

/**
 * @codeCoverageIgnore
 */
trait RegistryInjection
{
    protected Registry $registry;

    /**
     * @noinspection PhpUnused
     */
    public function injectRegistry(Registry $registry)
    {
        $this->registry = $registry;
    }
}
