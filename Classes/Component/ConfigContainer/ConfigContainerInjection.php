<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\ConfigContainer;

/**
 * @codeCoverageIgnore
 */
trait ConfigContainerInjection
{
    protected ConfigContainer $configContainer;

    /**
     * @noinspection PhpUnused
     */
    public function injectConfigContainer(ConfigContainer $configContainer): void
    {
        $this->configContainer = $configContainer;
    }
}
