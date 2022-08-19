<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core;

/**
 * @codeCoverageIgnore
 */
trait RecordIndexInjection
{
    protected RecordIndex $recordIndex;

    /**
     * @noinspection PhpUnused
     */
    public function injectRecordIndex(RecordIndex $recordIndex): void
    {
        $this->recordIndex = $recordIndex;
    }
}
