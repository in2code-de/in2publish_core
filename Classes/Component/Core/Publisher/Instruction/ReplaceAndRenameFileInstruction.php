<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher\Instruction;

use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\FalDriverService;
use TYPO3\CMS\Core\Utility\PathUtility;

class ReplaceAndRenameFileInstruction implements PublishInstruction
{
    protected int $storage;
    protected string $oldFileIdentifier;
    protected string $foreignTargetFileIdentifier;
    protected string $foreignTemporaryFileIdentifier;

    public function __construct(
        int $storage,
        string $oldFileIdentifier,
        string $foreignTargetFileIdentifier,
        string $foreignTemporaryFileIdentifier
    ) {
        $this->storage = $storage;
        $this->oldFileIdentifier = $oldFileIdentifier;
        $this->foreignTargetFileIdentifier = $foreignTargetFileIdentifier;
        $this->foreignTemporaryFileIdentifier = $foreignTemporaryFileIdentifier;
    }

    public function execute(FalDriverService $falDriverService): void
    {
        $driver = $falDriverService->getDriver($this->storage);

        $driver->renameFile($this->oldFileIdentifier, PathUtility::basename($this->foreignTargetFileIdentifier));
        $driver->replaceFile($this->foreignTargetFileIdentifier, $this->foreignTemporaryFileIdentifier);
    }

    public function getConfiguration(): array
    {
        return [
            'storage' => $this->storage,
            'oldFileIdentifier' => $this->oldFileIdentifier,
            'foreignTargetFileIdentifier' => $this->foreignTargetFileIdentifier,
            'foreignTemporaryFileIdentifier' => $this->foreignTemporaryFileIdentifier,
        ];
    }
}
