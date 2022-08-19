<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\PreProcessing\Service;

/**
 * @codeCoverageIgnore
 */
trait FlexFormFlatteningServiceInjection
{
    protected FlexFormFlatteningService $flexFormFlatteningService;

    /**
     * @noinspection PhpUnused
     */
    public function injectFlexFormFlatteningService(FlexFormFlatteningService $flexFormFlatteningService): void
    {
        $this->flexFormFlatteningService = $flexFormFlatteningService;
    }
}
