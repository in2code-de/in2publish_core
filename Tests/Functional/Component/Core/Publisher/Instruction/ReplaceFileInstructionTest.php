<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Functional\Component\Core\Publisher\Instruction;

use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\FalDriverService;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\ReplaceAndRenameFileInstruction;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\ReplaceFileInstruction;
use In2code\In2publishCore\Tests\FunctionalTestCase;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function CoStack\Lib\concat_paths;
use function file_put_contents;

class ReplaceFileInstructionTest extends FunctionalTestCase
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

        $instruction = new ReplaceFileInstruction(1, 'foo/baz/beng.txt', $tempFile);

        $targetFolder = concat_paths($this->testPath, 'foo/baz');
        GeneralUtility::mkdir_deep($targetFolder);
        $targetFile = concat_paths($targetFolder, 'beng.txt');
        file_put_contents($targetFile, 'Old Content');

        self::assertFileExists($tempFile);
        self::assertStringEqualsFile($targetFile, 'Old Content');
        $instruction->execute($falDriverService);
        self::assertFileDoesNotExist($tempFile);
        self::assertStringEqualsFile($targetFile, 'A movie');
    }
}
