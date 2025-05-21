<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Publisher\Instruction;

use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\FalDriverService;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\MoveFileInstruction;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;

#[CoversMethod(MoveFileInstruction::class, 'execute')]
class MoveFileInstructionTest extends UnitTestCase
{
    public function testExecutionCreatesFolder(): void
    {
        $driver = $this->createMock(LocalDriver::class);
        $driver->expects($this->once())->method('folderExists')->with('someOtherFolder')->willReturn(true);
        $driver->expects($this->once())->method('moveFileWithinStorage')->with(
            'new_folder/oldFile.txt',
            'someOtherFolder',
            'newFileName.txt',
        );

        $falDriverService = $this->createMock(FalDriverService::class);
        $falDriverService->method('getDriver')->willReturn($driver);
        $instruction = new MoveFileInstruction(1, 'new_folder/oldFile.txt', 'someOtherFolder/newFileName.txt');
        $instruction->execute($falDriverService);
    }
}
