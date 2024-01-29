<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Functional\Component\Core\Publisher\Instruction;

use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\FalDriverService;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\DeleteFolderInstruction;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\MoveFileInstruction;
use In2code\In2publishCore\Tests\FunctionalTestCase;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function CoStack\Lib\concat_paths;
use function touch;

class MoveFileInstructionTest extends FunctionalTestCase
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
        $instruction = new MoveFileInstruction(1, 'foo/baz/beng.txt', 'images/videos/audio.exe');

        $testFolder = concat_paths($this->testPath, 'foo/baz');
        GeneralUtility::mkdir_deep($testFolder);
        $testFile = concat_paths($testFolder, 'beng.txt');
        touch($testFile);
        $targetFile = concat_paths($this->testPath, 'images/videos/audio.exe');

        self::assertFileExists($testFile);
        self::assertFileDoesNotExist($targetFile);
        $instruction->execute($falDriverService);
        self::assertFileExists($targetFile);
        self::assertFileDoesNotExist($testFile);
    }
}
