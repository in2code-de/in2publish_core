<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Database;

/**
 * @codeCoverageIgnore
 */
trait RawRecordServiceInjection
{
    protected RawRecordService $rawRecordService;

    /**
     * @noinspection PhpUnused
     */
    public function injectRawRecordService(RawRecordService $rawRecordService): void
    {
        $this->rawRecordService = $rawRecordService;
    }
}
