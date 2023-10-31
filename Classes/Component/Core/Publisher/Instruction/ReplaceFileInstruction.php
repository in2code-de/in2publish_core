<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher\Instruction;

use In2code\In2publishCore\Component\Core\FileHandling\Service\FalDriverService;

class ReplaceFileInstruction implements PublishInstruction
{
    protected int $storage;
    protected string $foreignTargetFileIdentifier;
    protected string $foreignTemporaryFileIdentifier;

    public function __construct(
        int $storage,
        string $foreignTargetFileIdentifier,
        string $foreignTemporaryFileIdentifier
    ) {
        $this->storage = $storage;
        $this->foreignTargetFileIdentifier = $foreignTargetFileIdentifier;
        $this->foreignTemporaryFileIdentifier = $foreignTemporaryFileIdentifier;
    }

    public function execute(FalDriverService $falDriverService): void
    {
        $driver = $falDriverService->getDriver($this->storage);

        $driver->replaceFile($this->foreignTargetFileIdentifier, $this->foreignTemporaryFileIdentifier);
    }

    public function getConfiguration(): array
    {
        return [
            'storage' => $this->storage,
            'foreignTargetFileIdentifier' => $this->foreignTargetFileIdentifier,
            'foreignTemporaryFileIdentifier' => $this->foreignTemporaryFileIdentifier,
        ];
    }
}
