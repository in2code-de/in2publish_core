<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher\Instruction;

use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\FalDriverService;
use TYPO3\CMS\Core\Utility\PathUtility;

class MoveFileInstruction implements PublishInstruction
{
    protected int $storage;
    protected string $oldFileIdentifier;
    protected string $newFileIdentifier;

    public function __construct(int $storage, string $oldFileIdentifier, string $newFileIdentifier)
    {
        $this->storage = $storage;
        $this->oldFileIdentifier = $oldFileIdentifier;
        $this->newFileIdentifier = $newFileIdentifier;
    }

    public function execute(FalDriverService $falDriverService): void
    {
        $driver = $falDriverService->getDriver($this->storage);
        $newFolderName = PathUtility::dirname($this->newFileIdentifier);
        $newFileName = PathUtility::basename($this->newFileIdentifier);
        if (!$driver->folderExists($newFolderName)) {
            $driver->createFolder($newFolderName);
        }
        $driver->moveFileWithinStorage($this->oldFileIdentifier, $newFolderName, $newFileName);
    }

    public function getConfiguration(): array
    {
        return [
            'storage' => $this->storage,
            'oldFileIdentifier' => $this->oldFileIdentifier,
            'newFileIdentifier' => $this->newFileIdentifier,
        ];
    }
}
