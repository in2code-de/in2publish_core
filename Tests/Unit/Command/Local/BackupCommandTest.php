<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Command\Local;

use In2code\In2publishCore\Command\Foreign\Status\GlobalConfigurationCommand;
use In2code\In2publishCore\Command\Local\Table\BackupCommand;
use In2code\In2publishCore\Tests\UnitTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use TYPO3\CMS\Core\Database\Connection;

use TYPO3\CMS\Core\Log\Logger;

use const PHP_EOL;

class BackupCommandTest  extends UnitTestCase
{
    public function testCommandCanBeExecuted(): void
    {
        $connection = $this->createMock(Connection::class);

        $logger = $this->createMock(Logger::class);

        $input = new ArrayInput(['tableName' => 'tx_foo_bar']);
        $output = new BufferedOutput();

        $command = new BackupCommand($connection);
        $command->setLogger($logger);

        unset($GLOBALS['TYPO3_CONF_VARS']['BE']['adminOnly']);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem'] = 'truthy';

        $code = $command->run($input, $output);

        $this->assertSame(0, $code);
        $this->assertSame('Utf8Filesystem: truthy' . PHP_EOL . 'adminOnly: empty' . PHP_EOL, $output->fetch());
    }
}
