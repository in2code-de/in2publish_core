<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher\Instruction;

use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\FalDriverService;
use TYPO3\CMS\Core\Utility\PathUtility;

class AddFolderInstruction implements PublishInstruction
{
    protected int $storage;
    protected string $folderIdentifier;

    public function __construct(int $storage, string $folderIdentifier)
    {
        $this->storage = $storage;
        $this->folderIdentifier = $folderIdentifier;
    }

    public function execute(FalDriverService $falDriverService): void
    {
        $driver = $falDriverService->getDriver($this->storage);

        $folderName = PathUtility::basename($this->folderIdentifier);
        $parentFolder = PathUtility::dirname($this->folderIdentifier);
        $driver->createFolder($folderName, $parentFolder, true);
    }

    public function getConfiguration(): array
    {
        return [
            'storage' => $this->storage,
            'folderIdentifier' => $this->folderIdentifier,
        ];
    }
}
