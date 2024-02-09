<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Publisher\Instruction;

use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\FalDriverService;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\AddFolderInstruction;
use In2code\In2publishCore\Tests\UnitTestCase;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Publisher\Instruction\AddFolderInstruction
 */
class AddFolderInstructionTest extends UnitTestCase
{
    /**
     * @covers ::execute
     */
    public function testExecutionCreatesFolder(): void
    {
        $driver = $this->createMock(LocalDriver::class);
        $driver->expects($this->once())->method('createFolder')->with('new_folder', '.', true);

        $falDriverService = $this->createMock(FalDriverService::class);
        $falDriverService->method('getDriver')->willReturn($driver);
        $instruction = new AddFolderInstruction(1, 'new_folder');
        $instruction->execute($falDriverService);
    }
}
