<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Functional\Component\Core\Publisher\Instruction;

use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\FalDriverService;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\AddFileInstruction;
use In2code\In2publishCore\Tests\FunctionalTestCase;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function CoStack\Lib\concat_paths;
use function file_put_contents;

class AddFileInstructionTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;
    protected string $testPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testPath = concat_paths(Environment::getVarPath(), 'transient/test');
        GeneralUtility::mkdir_deep($this->testPath);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        GeneralUtility::rmdir($this->testPath, true);
    }

    public function testExecuteCreatesFile(): void
    {
        $testFile = concat_paths($this->testPath, 'testFile123.txt');
        file_put_contents($testFile, 'Some Content in the file');

        $driver = new LocalDriver(['basePath' => $this->testPath]);
        $driver->processConfiguration();
        $falDriverService = $this->createMock(FalDriverService::class);
        $falDriverService->method('getDriver')->willReturn($driver);
        $instruction = new AddFileInstruction(1, $testFile, 'foo/baz/file.txt');

        $targetDirectory = concat_paths($this->testPath, 'foo/baz');
        $targetFile = concat_paths($this->testPath, 'foo/baz/file.txt');

        self::assertDirectoryDoesNotExist($targetDirectory);
        $instruction->execute($falDriverService);
        self::assertDirectoryExists($targetDirectory);
        self::assertStringEqualsFile($targetFile, 'Some Content in the file');
        self::assertFileDoesNotExist($testFile);
    }
}
