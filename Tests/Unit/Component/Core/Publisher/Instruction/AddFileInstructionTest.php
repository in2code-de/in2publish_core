<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Publisher\Instruction;

use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\FalDriverService;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\AddFileInstruction;
use In2code\In2publishCore\Tests\UnitTestCase;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;

use function file_exists;
use function file_put_contents;
use function register_shutdown_function;
use function unlink;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Publisher\Instruction\AddFileInstruction
 */
class AddFileInstructionTest extends UnitTestCase
{
    /**
     * @covers ::execute
     */
    public function testExecutionCreatesFolderIfNotPresent(): void
    {
        $driver = $this->createMock(LocalDriver::class);
        $driver->expects($this->once())->method('folderExists')->with('new_folder/')->willReturn(false);
        $driver->expects($this->once())->method('createFolder')->with('new_folder', '.', true);
        $driver->expects($this->once())->method('addFile')->with('/tmp/foo.txt', 'new_folder/');
        $driver->expects($this->once())->method('fileExists')->with('new_folder/foo.txt')->willReturn(false);

        $falDriverService = $this->createMock(FalDriverService::class);
        $falDriverService->method('getDriver')->willReturn($driver);
        register_shutdown_function(static fn() => file_exists('/tmp/foo.txt') && unlink('/tmp/foo.txt'));
        file_put_contents('/tmp/foo.txt', 'TestString');
        $instruction = new AddFileInstruction(1, '/tmp/foo.txt', 'new_folder/foo.txt');
        $instruction->execute($falDriverService);
    }

    /**
     * @covers ::execute
     */
    public function testExecutionSkipsFolderCreationIfFolderIsPresent(): void
    {
        $driver = $this->createMock(LocalDriver::class);
        $driver->expects($this->once())->method('folderExists')->with('new_folder/')->willReturn(true);
        $driver->expects($this->never())->method('createFolder');
        $driver->expects($this->once())->method('addFile')->with('/tmp/foo.txt', 'new_folder/');
        $driver->expects($this->once())->method('fileExists')->with('new_folder/foo.txt')->willReturn(false);

        $falDriverService = $this->createMock(FalDriverService::class);
        $falDriverService->method('getDriver')->willReturn($driver);
        register_shutdown_function(static fn() => file_exists('/tmp/foo.txt') && unlink('/tmp/foo.txt'));
        file_put_contents('/tmp/foo.txt', 'TestString');
        $instruction = new AddFileInstruction(1, '/tmp/foo.txt', 'new_folder/foo.txt');
        $instruction->execute($falDriverService);
    }
}
