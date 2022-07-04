<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Command\Foreign\Status;

use In2code\In2publishCore\Command\Foreign\Status\SiteConfigurationCommand;
use In2code\In2publishCore\Tests\UnitTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;

use function base64_encode;
use function serialize;

use const PHP_EOL;

class SiteConfigurationCommandTest extends UnitTestCase
{
    /**
     * @ticket https://projekte.in2code.de/issues/51213
     * @covers ::execute
     */
    public function testCommandCanBeExecuted(): void
    {
        $siteFinder = $this->createMock(SiteFinder::class);
        $site = new Site('foo', 3, ['bar' => 'bza']);
        $siteFinder->method('getSiteByPageId')->willReturn($site);

        $input = new ArrayInput(['pageId' => '3']);
        $output = new BufferedOutput();

        $command = new SiteConfigurationCommand();
        $command->injectSiteFinder($siteFinder);

        $code = $command->run($input, $output);

        $this->assertSame(0, $code);
        $this->assertSame('Site: ' . base64_encode(serialize($site)) . PHP_EOL, $output->fetch());
    }
}
