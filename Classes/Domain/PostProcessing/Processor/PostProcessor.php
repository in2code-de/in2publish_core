<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\PostProcessing\Processor;

use In2code\In2publishCore\Domain\Model\RecordInterface;

interface PostProcessor
{
    /** @param RecordInterface[] $records */
    public function postProcess(array $records): void;
}
