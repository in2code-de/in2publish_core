<?php

declare(strict_types=1);

namespace In2code\In2publishCore\CommonInjection;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * @codeCoverageIgnore
 */
trait ExtensionConfigurationInjection
{
    protected ExtensionConfiguration $extensionConfiguration;

    /**
     * @noinspection PhpUnused
     */
    public function injectExtensionConfiguration(ExtensionConfiguration $extensionConfiguration): void
    {
        $this->extensionConfiguration = $extensionConfiguration;
    }
}
