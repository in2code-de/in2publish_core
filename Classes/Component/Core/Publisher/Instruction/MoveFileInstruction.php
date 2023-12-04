<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher\Instruction;

use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\FalDriverService;
use TYPO3\CMS\Core\Utility\PathUtility;

use function trim;

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
            $childFolder = trim(PathUtility::basename($newFolderName), '/');
            $parentFolder = trim(PathUtility::dirname($newFolderName), '/');
            $driver->createFolder($childFolder, $parentFolder, true);
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
