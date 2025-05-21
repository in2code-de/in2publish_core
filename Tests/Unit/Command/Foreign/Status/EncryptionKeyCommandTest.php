<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Command\Foreign\Status;

use In2code\In2publishCore\Command\Foreign\Status\EncryptionKeyCommand;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use const PHP_EOL;

#[CoversMethod(EncryptionKeyCommand::class, 'execute')]
class EncryptionKeyCommandTest extends UnitTestCase
{
    /**
     * ticket https://projekte.in2code.de/issues/51213
     */
    public function testCommandCanBeExecuted(): void
    {
        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $command = new EncryptionKeyCommand();

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'foo foo bar bar';

        $code = $command->run($input, $output);

        $this->assertSame(0, $code);
        $this->assertSame('EKey: Zm9vIGZvbyBiYXIgYmFy' . PHP_EOL, $output->fetch());
    }
}
