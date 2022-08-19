<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\RemoteProcedureCall;

/**
 * @codeCoverageIgnore
 */
trait LetterboxInjection
{
    protected Letterbox $letterbox;

    /**
     * @noinspection PhpUnused
     */
    public function injectLetterbox(Letterbox $letterbox): void
    {
        $this->letterbox = $letterbox;
    }
}
