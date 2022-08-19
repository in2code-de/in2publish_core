<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\ConfigContainer;

/**
 * @codeCoverageIgnore
 */
trait ValidationContainerInjection
{
    protected ValidationContainer $validationContainer;

    /**
     * @noinspection PhpUnused
     */
    public function injectValidationContainer(ValidationContainer $validationContainer): void
    {
        $this->validationContainer = $validationContainer;
    }
}
