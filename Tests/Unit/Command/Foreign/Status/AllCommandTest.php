<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Command\Foreign\Status;

use In2code\In2publishCore\Command\Foreign\Status\AllCommand;
use In2code\In2publishCore\Tests\UnitTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Console\CommandRegistry;

class AllCommandTest extends UnitTestCase
{
    public function testCommandCanBeExecuted(): void
    {
        $commandRegistry = $this->createMock(CommandRegistry::class);
        $commandRegistry->method('getCommandByIdentifier')->willReturn(new class() extends Command {
            public function execute(InputInterface $input, OutputInterface $output)
            {
                return 0;
            }
        });

        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $command = new AllCommand($commandRegistry);

        $code = $command->run($input, $output);

        $this->assertSame(0, $code);
    }
}
