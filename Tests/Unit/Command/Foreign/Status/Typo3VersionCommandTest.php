<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Command\Foreign\Status;

use In2code\In2publishCore\Command\Foreign\Status\Typo3VersionCommand;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use TYPO3\CMS\Core\Information\Typo3Version;

use const PHP_EOL;

#[CoversMethod(Typo3VersionCommand::class, 'execute')]
class Typo3VersionCommandTest extends UnitTestCase
{
    /**
     * ticket https://projekte.in2code.de/issues/51213
     */
    public function testCommandCanBeExecuted(): void
    {
        $typo3Version = $this->createMock(Typo3Version::class);
        $typo3Version->method('getVersion')->willReturn('12.34.56');

        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $command = new Typo3VersionCommand();
        $command->injectTypo3Version($typo3Version);

        $code = $command->run($input, $output);

        $this->assertSame(0, $code);
        $this->assertSame('TYPO3: 12.34.56' . PHP_EOL, $output->fetch());
    }
}
