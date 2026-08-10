<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher\Instruction;

interface ProcessedFileCleanupInstruction
{
    public function getStorage(): int;

    /** @return list<string> */
    public function getFileIdentifiersForProcessedFileCleanup(): array;

    /** @return list<string> */
    public function getFolderIdentifiersForProcessedFileCleanup(): array;
}
