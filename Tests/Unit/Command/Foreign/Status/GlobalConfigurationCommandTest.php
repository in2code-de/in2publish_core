<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Command\Foreign\Status;

use In2code\In2publishCore\Command\Foreign\Status\GlobalConfigurationCommand;
use In2code\In2publishCore\Tests\UnitTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use const PHP_EOL;

class GlobalConfigurationCommandTest extends UnitTestCase
{
    public function testCommandCanBeExecuted(): void
    {
        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $command = new GlobalConfigurationCommand();

        unset($GLOBALS['TYPO3_CONF_VARS']['BE']['adminOnly']);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem'] = 'truthy';

        $code = $command->run($input, $output);

        $this->assertSame(0, $code);
        $this->assertSame('Utf8Filesystem: truthy' . PHP_EOL . 'adminOnly: empty' . PHP_EOL, $output->fetch());
    }
}
