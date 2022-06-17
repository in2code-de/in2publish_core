<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\PostPublishTaskExecution\Command\Foreign;

use In2code\In2publishCore\Component\PostPublishTaskExecution\Command\Foreign\RunTasksInQueueCommand;
use In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Repository\TaskRepository;
use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Tests\UnitTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class RunTasksInQueueCommandTest extends UnitTestCase
{
    /**
     * @ticket https://projekte.in2code.de/issues/51213
     */
    public function testCommandCanBeExecuted(): void
    {
        $contextService = $this->createMock(ContextService::class);
        $contextService->method('isForeign')->willReturn(true);

        $taskRepository = $this->createMock(TaskRepository::class);
        $taskRepository->method('findByExecutionBegin')->willReturn([]);

        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $command = new RunTasksInQueueCommand($contextService, $taskRepository);

        $code = $command->run($input, $output);

        $this->assertSame(0, $code);
        $this->assertSame('["There was nothing to execute"]', $output->fetch());
    }
}
