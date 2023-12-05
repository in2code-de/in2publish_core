<?php

declare(strict_types=1);

namespace In2code\In2publishCore\CommonInjection;

use Psr\Container\ContainerInterface;

/**
 * @codeCoverageIgnore
 */
trait ContainerInjection
{
    protected ContainerInterface $container;

    /**
     * @noinspection PhpUnused
     */
    public function injectContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }
}
