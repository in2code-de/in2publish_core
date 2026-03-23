<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Browser;

use CoStack\ProcessManager\ProcessManager;
use CoStack\StackTest\Test\Assert\DriverAssertions;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

use function register_shutdown_function;

abstract class AbstractBrowserTestCase extends TestCase
{
    use DriverAssertions;

    // Sleep Time used for "Workaround Sleeps"
    protected $sleepTime = 3;

    protected function setUp(): void
    {
        $env = [];
        $env['XDEBUG_SESSION'] = 0;
        $env['PHP_IDE_CONFIG'] = 'serverName=local.v13.in2publish-core.de';

        $processes = [];
        $processes[] = new Process(
            [
                'rsync',
                '-a',
                '--delete',
                '--verbose',
                '/app/.project/data/fileadmin/local/',
                '/app/Build/local/public/fileadmin/',
            ],
            env: $env,
        );
        $processes[] = new Process(
            [
                'rsync',
                '-a',
                '--delete',
                '--verbose',
                '/app/.project/data/fileadmin/foreign/',
                '/app/Build/foreign/public/fileadmin/',
            ],
            env: $env,
        );
        $processes[] = new Process(
            ['/.project/scripts/restore-db.sh', 'local', 'base'],
            env: $env,
        );
        $processes[] = new Process(
            ['/.project/scripts/restore-db.sh', 'foreign', 'base'],
            env: $env,
        );
        register_shutdown_function(static function () use ($processes) {
            foreach ($processes as $process) {
                /** @var Process $process */
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
        });

        $processManager = new ProcessManager();
        $processManager->runParallel($processes, 4);

        $outputs = [];
        $errors = [];

        foreach ($processes as $process) {
            $outputs[] = $process->getOutput();
            $errors[] = $process->getErrorOutput();
            if (!$process->isSuccessful()) {
                throw new Exception(
                    'Error while setup: ' . $process->getOutput() . "\n" . $process->getErrorOutput(), 4061840895,
                );
            }
        }
    }
}
