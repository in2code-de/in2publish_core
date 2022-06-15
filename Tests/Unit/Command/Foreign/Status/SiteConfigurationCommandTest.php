<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Command\Foreign\Status;

use In2code\In2publishCore\Command\Foreign\Status\SiteConfigurationCommand;
use In2code\In2publishCore\Tests\UnitTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;

use const PHP_EOL;

class SiteConfigurationCommandTest extends UnitTestCase
{
    public function testCommandCanBeExecuted(): void
    {
        $siteFinder = $this->createMock(SiteFinder::class);
        $site = new Site('foo', 3, ['bar' => 'bza']);
        $siteFinder->method('getSiteByPageId')->willReturn($site);

        $input = new ArrayInput(['pageId' => '3']);
        $output = new BufferedOutput();

        $command = new SiteConfigurationCommand($siteFinder);

        $code = $command->run($input, $output);

        $this->assertSame(0, $code);
        $this->assertSame(
            'Site: TzozMToiVFlQTzNcQ01TXENvcmVcU2l0ZVxFbnRpdHlcU2l0ZSI6Njp7czoxMzoiACoAaWRlbnRpZmllciI7czozOiJmb28iO3M6'
            . 'NzoiACoAYmFzZSI7TzoyMzoiVFlQTzNcQ01TXENvcmVcSHR0cFxVcmkiOjk6e3M6OToiACoAc2NoZW1lIjtOO3M6MTk6IgAqAHN1cHBv'
            . 'cnRlZFNjaGVtZXMiO2E6Mjp7czo0OiJodHRwIjtpOjgwO3M6NToiaHR0cHMiO2k6NDQzO31zOjEyOiIAKgBhdXRob3JpdHkiO3M6MDoi'
            . 'IjtzOjExOiIAKgB1c2VySW5mbyI7czowOiIiO3M6NzoiACoAaG9zdCI7czowOiIiO3M6NzoiACoAcG9ydCI7TjtzOjc6IgAqAHBhdGgi'
            . 'O3M6MDoiIjtzOjg6IgAqAHF1ZXJ5IjtzOjA6IiI7czoxMToiACoAZnJhZ21lbnQiO047fXM6MTM6IgAqAHJvb3RQYWdlSWQiO2k6Mztz'
            . 'OjE2OiIAKgBjb25maWd1cmF0aW9uIjthOjE6e3M6MzoiYmFyIjtzOjM6ImJ6YSI7fXM6MTI6IgAqAGxhbmd1YWdlcyI7YToxOntpOjA7'
            . 'TzozOToiVFlQTzNcQ01TXENvcmVcU2l0ZVxFbnRpdHlcU2l0ZUxhbmd1YWdlIjoxNTp7czoxMzoiACoAbGFuZ3VhZ2VJZCI7aTowO3M6'
            . 'OToiACoAbG9jYWxlIjtzOjExOiJlbl9VUy5VVEYtOCI7czo3OiIAKgBiYXNlIjtPOjIzOiJUWVBPM1xDTVNcQ29yZVxIdHRwXFVyaSI6'
            . 'OTp7czo5OiIAKgBzY2hlbWUiO047czoxOToiACoAc3VwcG9ydGVkU2NoZW1lcyI7YToyOntzOjQ6Imh0dHAiO2k6ODA7czo1OiJodHRw'
            . 'cyI7aTo0NDM7fXM6MTI6IgAqAGF1dGhvcml0eSI7czowOiIiO3M6MTE6IgAqAHVzZXJJbmZvIjtzOjA6IiI7czo3OiIAKgBob3N0Ijtz'
            . 'OjA6IiI7czo3OiIAKgBwb3J0IjtOO3M6NzoiACoAcGF0aCI7czoxOiIvIjtzOjg6IgAqAHF1ZXJ5IjtzOjA6IiI7czoxMToiACoAZnJh'
            . 'Z21lbnQiO047fXM6ODoiACoAdGl0bGUiO3M6NzoiRGVmYXVsdCI7czoxODoiACoAbmF2aWdhdGlvblRpdGxlIjtzOjA6IiI7czoxNToi'
            . 'ACoAd2Vic2l0ZVRpdGxlIjtzOjA6IiI7czoxNzoiACoAZmxhZ0lkZW50aWZpZXIiO3M6ODoiZmxhZ3MtdXMiO3M6MTk6IgAqAHR3b0xl'
            . 'dHRlcklzb0NvZGUiO3M6MjoiZW4iO3M6MTE6IgAqAGhyZWZsYW5nIjtzOjU6ImVuLVVTIjtzOjEyOiIAKgBkaXJlY3Rpb24iO3M6MDoi'
            . 'IjtzOjE2OiIAKgB0eXBvM0xhbmd1YWdlIjtzOjc6ImRlZmF1bHQiO3M6MTU6IgAqAGZhbGxiYWNrVHlwZSI7czo2OiJzdHJpY3QiO3M6'
            . 'MjI6IgAqAGZhbGxiYWNrTGFuZ3VhZ2VJZHMiO2E6MDp7fXM6MTA6IgAqAGVuYWJsZWQiO2I6MTtzOjE2OiIAKgBjb25maWd1cmF0aW9u'
            . 'IjthOjk6e3M6MTA6Imxhbmd1YWdlSWQiO2k6MDtzOjU6InRpdGxlIjtzOjc6IkRlZmF1bHQiO3M6MTU6Im5hdmlnYXRpb25UaXRsZSI7'
            . 'czowOiIiO3M6MTM6InR5cG8zTGFuZ3VhZ2UiO3M6NzoiZGVmYXVsdCI7czo0OiJmbGFnIjtzOjg6ImZsYWdzLXVzIjtzOjY6ImxvY2Fs'
            . 'ZSI7czoxMToiZW5fVVMuVVRGLTgiO3M6OToiaXNvLTYzOS0xIjtzOjI6ImVuIjtzOjg6ImhyZWZsYW5nIjtzOjU6ImVuLVVTIjtzOjk6'
            . 'ImRpcmVjdGlvbiI7czowOiIiO319fXM6MTY6IgAqAGVycm9ySGFuZGxlcnMiO047fQ=='
            . PHP_EOL,
            $output->fetch()
        );
    }
}
