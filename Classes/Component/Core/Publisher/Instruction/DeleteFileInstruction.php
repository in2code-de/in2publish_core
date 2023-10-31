<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher\Instruction;

use In2code\In2publishCore\Component\Core\FileHandling\Service\FalDriverService;

class DeleteFileInstruction implements PublishInstruction
{
    protected int $storage;
    protected string $fileIdentifier;

    public function __construct(int $storage, string $fileIdentifier)
    {
        $this->storage = $storage;
        $this->fileIdentifier = $fileIdentifier;
    }

    public function execute(FalDriverService $falDriverService): void
    {
        $driver = $falDriverService->getDriver($this->storage);
        $driver->deleteFile($this->fileIdentifier);
    }

    public function getConfiguration(): array
    {
        return [
            'storage' => $this->storage,
            'fileIdentifier' => $this->fileIdentifier,
        ];
    }
}
