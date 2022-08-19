<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Record\Factory;

/**
 * @codeCoverageIgnore
 */
trait RecordFactoryInjection
{
    protected RecordFactory $recordFactory;

    /**
     * @noinspection PhpUnused
     */
    public function injectRecordFactory(RecordFactory $recordFactory): void
    {
        $this->recordFactory = $recordFactory;
    }
}
