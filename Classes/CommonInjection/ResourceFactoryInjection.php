<?php

declare(strict_types=1);

namespace In2code\In2publishCore\CommonInjection;

use TYPO3\CMS\Core\Resource\ResourceFactory;

/**
 * @codeCoverageIgnore
 */
trait ResourceFactoryInjection
{
    protected ResourceFactory $resourceFactory;

    /**
     * @noinspection PhpUnused
     */
    public function injectResourceFactory(ResourceFactory $resourceFactory): void
    {
        $this->resourceFactory = $resourceFactory;
    }
}
