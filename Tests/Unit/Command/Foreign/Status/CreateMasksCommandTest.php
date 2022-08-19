<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Command\Foreign\Status;

use In2code\In2publishCore\Command\Foreign\Status\CreateMasksCommand;
use In2code\In2publishCore\Tests\UnitTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use const PHP_EOL;

/**
 * @coversDefaultClass \In2code\In2publishCore\Command\Foreign\Status\CreateMasksCommand
 */
class CreateMasksCommandTest extends UnitTestCase
{
    /**
     * @ticket https://projekte.in2code.de/issues/51213
     * @covers ::execute
     */
    public function testCommandCanBeExecuted(): void
    {
        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $command = new CreateMasksCommand();

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'] = '0664';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'] = '0775';

        $code = $command->run($input, $output);

        $this->assertSame(0, $code);
        $this->assertSame('FileCreateMask: 0664' . PHP_EOL . 'FolderCreateMask: 0775' . PHP_EOL, $output->fetch());
    }
}
