<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Publisher\Instruction;

use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\FalDriverService;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\DeleteFileInstruction;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;

#[CoversMethod(DeleteFileInstruction::class, 'execute')]
class DeleteFileInstructionTest extends UnitTestCase
{
    public function testExecutionCreatesFolder(): void
    {
        $driver = $this->createMock(LocalDriver::class);
        $driver->expects($this->once())->method('deleteFile')->with('new_folder/foo.txt');

        $falDriverService = $this->createMock(FalDriverService::class);
        $falDriverService->method('getDriver')->willReturn($driver);
        $instruction = new DeleteFileInstruction(1, 'new_folder/foo.txt');
        $instruction->execute($falDriverService);
    }
}
