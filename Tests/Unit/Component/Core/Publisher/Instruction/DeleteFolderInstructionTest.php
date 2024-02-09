<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Publisher\Instruction;

use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\FalDriverService;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\DeleteFolderInstruction;
use In2code\In2publishCore\Tests\UnitTestCase;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Publisher\Instruction\DeleteFolderInstruction
 */
class DeleteFolderInstructionTest extends UnitTestCase
{
    /**
     * @covers ::execute
     */
    public function testExecutionCreatesFolder(): void
    {
        $driver = $this->createMock(LocalDriver::class);
        $driver->expects($this->once())->method('deleteFolder')->with('new_folder/subfolder', true);

        $falDriverService = $this->createMock(FalDriverService::class);
        $falDriverService->method('getDriver')->willReturn($driver);
        $instruction = new DeleteFolderInstruction(1, 'new_folder/subfolder');
        $instruction->execute($falDriverService);
    }
}
