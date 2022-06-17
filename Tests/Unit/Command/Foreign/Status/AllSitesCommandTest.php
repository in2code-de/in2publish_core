<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Command\Foreign\Status;

use In2code\In2publishCore\Command\Foreign\Status\AllSitesCommand;
use In2code\In2publishCore\Tests\UnitTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;

use function base64_encode;
use function serialize;

use const PHP_EOL;

class AllSitesCommandTest extends UnitTestCase
{
    /**
     * @ticket https://projekte.in2code.de/issues/51213
     */
    public function testCommandCanBeExecuted(): void
    {
        $siteFinder = $this->createMock(SiteFinder::class);
        $site = new Site('foo', 3, ['bar' => 'bza']);
        $siteFinder->method('getAllSites')->willReturn([$site]);

        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $command = new AllSitesCommand($siteFinder);

        $code = $command->run($input, $output);

        $expected = 'Sites: ' . base64_encode(serialize([$site])) . PHP_EOL;

        $this->assertSame(0, $code);
        $this->assertSame($expected, $output->fetch());
    }
}
