<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\RecordTree;

/**
 * @codeCoverageIgnore
 */
trait RecordTreeBuilderInjection
{
    protected RecordTreeBuilder $recordTreeBuilder;

    /**
     * @noinspection PhpUnused
     */
    public function injectRecordTreeBuilder(RecordTreeBuilder $recordTreeBuilder): void
    {
        $this->recordTreeBuilder = $recordTreeBuilder;
    }
}
