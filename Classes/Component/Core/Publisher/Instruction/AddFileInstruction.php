<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher\Instruction;

use In2code\In2publishCore\Component\Core\FileHandling\Service\FalDriverService;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Utility\PathUtility;

use function file_exists;
use function trim;

class AddFileInstruction implements PublishInstruction
{
    protected int $storage;
    protected string $foreignTemporaryFileIdentifier;
    protected string $foreignTargetFileIdentifier;

    public function __construct(
        int $storage,
        string $foreignTemporaryFileIdentifier,
        string $foreignTargetFileIdentifier
    ) {
        $this->storage = $storage;
        $this->foreignTemporaryFileIdentifier = $foreignTemporaryFileIdentifier;
        $this->foreignTargetFileIdentifier = $foreignTargetFileIdentifier;
    }

    public function execute(FalDriverService $falDriverService): void
    {
        $driver = $falDriverService->getDriver($this->storage);

        $targetFolderName = trim(PathUtility::dirname($this->foreignTargetFileIdentifier), '/') . '/';
        $this->createParentFolderIfRequired($driver, $targetFolderName);
        // only add file if it does not exist on foreign
        // otherwise FalPublisherExecutionFailedException is thrown because there is no more file in transient folder
        if (
            !$driver->fileExists($this->foreignTargetFileIdentifier)
            && file_exists($this->foreignTemporaryFileIdentifier)
        ) {
            $targetFileName = PathUtility::basename($this->foreignTargetFileIdentifier);
            $driver->addFile($this->foreignTemporaryFileIdentifier, $targetFolderName, $targetFileName);
        }
    }

    protected function createParentFolderIfRequired(DriverInterface $driver, string $targetDir): void
    {
        if (!$driver->folderExists($targetDir)) {
            $folderName = PathUtility::basename($targetDir);
            $parentFolder = PathUtility::dirname($targetDir);
            $driver->createFolder($folderName, $parentFolder, true);
        }
    }

    public function getConfiguration(): array
    {
        return [
            'storage' => $this->storage,
            'foreignTemporaryFileIdentifier' => $this->foreignTemporaryFileIdentifier,
            'foreignTargetFileIdentifier' => $this->foreignTargetFileIdentifier,
        ];
    }
}
