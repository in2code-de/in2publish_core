<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Functional\Component\Core\Publisher\Instruction;

use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\FalDriverService;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\MoveFileInstruction;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\ReplaceAndRenameFileInstruction;
use In2code\In2publishCore\Tests\FunctionalTestCase;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function CoStack\Lib\concat_paths;
use function file_put_contents;
use function touch;

class ReplaceAndRenameFileInstructionTest extends FunctionalTestCase
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
        $driver = new LocalDriver(['basePath' => $this->testPath]);
        $driver->processConfiguration();
        $falDriverService = $this->createMock(FalDriverService::class);
        $falDriverService->method('getDriver')->willReturn($driver);

        $tempFolder = concat_paths($this->testPath, 'tmp');
        GeneralUtility::mkdir_deep($tempFolder);
        $tempFile = concat_paths($tempFolder, 'asdf.movie');
        file_put_contents($tempFile, 'A movie');

        $instruction = new ReplaceAndRenameFileInstruction(1, 'foo/baz/beng.txt', 'foo/baz/audio.exe', $tempFile);

        $testFolder = concat_paths($this->testPath, 'foo/baz');
        GeneralUtility::mkdir_deep($testFolder);
        $testFile = concat_paths($testFolder, 'beng.txt');
        file_put_contents($testFile, 'Old Content');

        $targetFile = concat_paths($this->testPath, 'foo/baz/audio.exe');

        self::assertFileExists($testFile);
        self::assertFileExists($tempFile);
        self::assertFileDoesNotExist($targetFile);
        $instruction->execute($falDriverService);
        self::assertFileDoesNotExist($testFile);
        self::assertFileDoesNotExist($tempFile);
        self::assertStringEqualsFile($targetFile, 'A movie');
    }

    public function testExecuteMovesFileToDifferentFolder(): void
    {
        self::markTestSkipped('Skipped because this functionality does not seem to be required.');
        $driver = new LocalDriver(['basePath' => $this->testPath]);
        $driver->processConfiguration();
        $falDriverService = $this->createMock(FalDriverService::class);
        $falDriverService->method('getDriver')->willReturn($driver);

        $tempFolder = concat_paths($this->testPath, 'tmp');
        GeneralUtility::mkdir_deep($tempFolder);
        $tempFile = concat_paths($tempFolder, 'asdf.movie');
        file_put_contents($tempFile, 'A movie');

        $instruction = new ReplaceAndRenameFileInstruction(1, 'foo/baz/beng.txt', 'video/image/audio.exe', $tempFile);

        $testFolder = concat_paths($this->testPath, 'foo/baz');
        GeneralUtility::mkdir_deep($testFolder);
        $testFile = concat_paths($testFolder, 'beng.txt');
        file_put_contents($testFile, 'Old Content');

        $targetFile = concat_paths($this->testPath, 'foo/baz/audio.exe');

        self::assertFileExists($testFile);
        self::assertFileExists($tempFile);
        self::assertFileDoesNotExist($targetFile);
        $instruction->execute($falDriverService);
        self::assertFileDoesNotExist($testFile);
        self::assertFileDoesNotExist($tempFile);
        self::assertStringEqualsFile($targetFile, 'A movie');
    }
}
