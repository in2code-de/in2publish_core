<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Command\Foreign\Status;

use In2code\In2publishCore\Command\Foreign\Status\ConfigFormatTestCommand;
use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Config\Node\NodeCollection;
use In2code\In2publishCore\Config\ValidationContainer;
use In2code\In2publishCore\Tests\UnitTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use const PHP_EOL;

/**
 * @coversDefaultClass \In2code\In2publishCore\Command\Foreign\Status\ConfigFormatTestCommand
 */
class ConfigFormatTestCommandTest extends UnitTestCase
{
    /**
     * @ticket https://projekte.in2code.de/issues/51213
     * @covers ::execute
     */
    public function testCommandCanBeExecuted(): void
    {
        $validationContainer = $this->createMock(ValidationContainer::class);
        $validationContainer->method('getErrors')->willReturn([
            [
                'configuration' => 'fii',
            ],
            [
                'configuration' => 'faa',
            ],
        ]);
        $configContainer = $this->createMock(ConfigContainer::class);
        $node = new NodeCollection();
        $configContainer->method('getForeignDefinition')->willReturn($node);
        $configContainer->method('get')->willReturn([]);

        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $command = new ConfigFormatTestCommand();
        $command->injectValidationContainer($validationContainer);
        $command->injectConfigContainer($configContainer);

        $code = $command->run($input, $output);

        $this->assertSame(0, $code);
        $this->assertSame('Config Format Test: WyJmaWkiLCJmYWEiXQ==' . PHP_EOL, $output->fetch());
    }
}
