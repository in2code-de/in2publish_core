<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Tests\Performance;

/*
 * Copyright notice
 *
 * (c) 2020 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use In2code\In2publishCore\Communication\AdapterRegistry;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteAdapter\AdapterInterface;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteAdapter\SshAdapter;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\In2publishCoreException;
use In2code\In2publishCore\Testing\Tests\Application\ForeignInstanceTest;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use In2code\In2publishHttp\Communication\RemoteCommandExecution\RemoteAdapter\HttpAdapter;
use In2code\In2publishSeclib\Communication\RemoteCommandExecution\RemoteAdapter\PhpSecLibAdapter;
use ReflectionException;
use ReflectionProperty;

use function array_sum;
use function array_unshift;
use function count;
use function function_exists;
use function microtime;
use function xdebug_is_enabled;

class RceInitializationPerformanceTest implements TestCaseInterface
{
    // TODO: let adapters provide their expected execution time themselves
    protected const THRESHOLD = [
        SshAdapter::class => [
            TestResult::OK => 0.3,
        ],
        PhpSecLibAdapter::class => [
            TestResult::OK => 1.1,
        ],
        HttpAdapter::class => [
            TestResult::OK => 0.2,
        ],
    ];

    /** @var AdapterRegistry */
    private $adapterRegistry;

    /** @var RemoteCommandDispatcher */
    private $remoteCommandDispatcher;

    public function __construct(AdapterRegistry $adapterRegistry, RemoteCommandDispatcher $remoteCommandDispatcher)
    {
        $this->adapterRegistry = $adapterRegistry;
        $this->remoteCommandDispatcher = $remoteCommandDispatcher;
    }

    /**
     * @throws ReflectionException
     * @throws In2publishCoreException
     */
    public function run(): TestResult
    {
        $adapterClass = $this->adapterRegistry->getAdapter(AdapterInterface::class);

        $request = new RemoteCommandRequest('echo "test"');
        $request->usePhp(false);
        $request->setDispatcher('');

        $times = [];

        $adapterProperty = new ReflectionProperty($this->remoteCommandDispatcher, 'adapter');
        $adapterProperty->setAccessible(true);

        for ($i = 0; $i < 3; $i++) {
            $start = microtime(true);

            /** @noinspection PhpRedundantOptionalArgumentInspection */
            $adapterProperty->setValue($this->remoteCommandDispatcher, null);
            $this->remoteCommandDispatcher->dispatch($request);

            $times[] = microtime(true) - $start;
        }

        $median = array_sum($times) / count($times);
        $messages = [];
        foreach ($times as $idx => $time) {
            $messages[] = 'Run ' . ($idx + 1) . ': ' . $time;
        }

        $severity = TestResult::WARNING;
        if ($median < self::THRESHOLD[$adapterClass][TestResult::OK]) {
            $severity = TestResult::OK;
        }
        if ($severity !== TestResult::OK) {
            array_unshift($messages, 'performance.rce_init.slow_help');
        }
        /** @noinspection ForgottenDebugOutputInspection */
        if (function_exists('xdebug_is_enabled') && xdebug_is_enabled()) {
            $severity = TestResult::WARNING;
            array_unshift($messages, 'performance.rce_init.xdebug_enabled');
        }

        return new TestResult(
            'performance.rce_init.init_time',
            $severity,
            $messages,
            [(string)$median]
        );
    }

    public function getDependencies(): array
    {
        return [
            ForeignInstanceTest::class,
        ];
    }
}
