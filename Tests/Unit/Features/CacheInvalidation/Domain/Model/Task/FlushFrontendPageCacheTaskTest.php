<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Features\CacheInvalidation\Domain\Model\Task;

/*
 * Copyright notice
 *
 * (c) 2026 in2code.de and the following authors:
 * Christine Zoglmeier <christine.zoglmeier@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use In2code\In2publishCore\Features\CacheInvalidation\Domain\Model\Task\FlushFrontendPageCacheTask;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use TYPO3\CMS\Core\DataHandling\DataHandler;

#[CoversMethod(FlushFrontendPageCacheTask::class, 'executeTask')]
class FlushFrontendPageCacheTaskTest extends UnitTestCase
{
    /** @var array<string> */
    protected array $executedCacheCommands = [];

    public static function configurationDataProvider(): array
    {
        return [
            'integer pid' => [4711, ['4711']],
            'string pid' => ['4711', ['4711']],
            'comma separated pid list' => ['1, 2 ,3', ['1', '2', '3']],
            'comma separated pid list with empty values' => ['1,,3,', ['1', '3']],
            'clear cache command' => ['pages', ['pages']],
            'cache tag command' => ['cacheTag:pagetag1', ['cacheTag:pagetag1']],
        ];
    }

    /**
     * @param int|string $pid
     * @param array<string> $expectedCacheCommands
     */
    #[DataProvider('configurationDataProvider')]
    public function testTaskFlushesCacheForAllConfiguredCommands($pid, array $expectedCacheCommands): void
    {
        $task = $this->createTask(['pid' => $pid]);

        $success = $task->execute();

        $expectedMessages = [];
        foreach ($expectedCacheCommands as $expectedCacheCommand) {
            $expectedMessages[] = 'Cleared frontend cache with configuration clearCacheCmd=' . $expectedCacheCommand;
        }
        $this->assertTrue($success);
        $this->assertSame($expectedCacheCommands, $this->executedCacheCommands);
        $this->assertSame($expectedMessages, $task->getMessages());
    }

    public static function emptyConfigurationDataProvider(): array
    {
        return [
            'missing pid' => [[]],
            'empty pid' => [['pid' => '']],
            'pid containing only separators' => [['pid' => ',,']],
        ];
    }

    #[DataProvider('emptyConfigurationDataProvider')]
    public function testTaskFailsWithoutFlushingAnyCacheIfNoCommandIsConfigured(array $configuration): void
    {
        $task = $this->createTask($configuration);

        $success = $task->execute();

        $this->assertFalse($success);
        $this->assertSame([], $this->executedCacheCommands);
        $this->assertSame(
            ['Skipped flushing the frontend cache because the task configuration contains no PID'],
            $task->getMessages(),
        );
    }

    /**
     * The DataHandler is replaced, so the task's command resolution can be tested in isolation.
     */
    protected function createTask(array $configuration): FlushFrontendPageCacheTask
    {
        $dataHandler = $this->createMock(DataHandler::class);
        $dataHandler->method('clear_cacheCmd')->willReturnCallback(
            function ($cacheCmd): void {
                $this->executedCacheCommands[] = $cacheCmd;
            },
        );

        $task = new class ($configuration) extends FlushFrontendPageCacheTask {
            public DataHandler $dataHandler;

            protected function getDataHandler(): DataHandler
            {
                return $this->dataHandler;
            }
        };
        $task->dataHandler = $dataHandler;

        return $task;
    }
}
