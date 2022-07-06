<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Controller\Traits;

trait DeactivateErrorFlashMessage
{
    /** Deactivate error messages in flash messages by explicitly returning false */
    protected function getErrorFlashMessage(): bool
    {
        return false;
    }
}
