<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Command\Foreign\Status;

use In2code\In2publishCore\Command\Foreign\Status\ShortSiteConfigurationCommand;
use In2code\In2publishCore\Tests\UnitTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;

use const PHP_EOL;

class ShortSiteConfigurationCommandTest extends UnitTestCase
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

        $command = new ShortSiteConfigurationCommand();
        $command->injectSiteFinder($siteFinder);

        $code = $command->run($input, $output);

        $this->assertSame(0, $code);
        $this->assertSame(
            'ShortSiteConfig: eyJmb28iOnsiYmFzZSI6IiIsInJvb3RQYWdlSWQiOjN9fQ==' . PHP_EOL,
            $output->fetch()
        );
    }
}
