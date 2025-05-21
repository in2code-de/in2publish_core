<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Log\Processor;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
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

use In2code\In2publishCore\Log\Processor\BackendUserProcessor;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Log\LogRecord;

#[CoversMethod(BackendUserProcessor::class, '__construct')]
#[CoversMethod(BackendUserProcessor::class, 'getBackendUser')]
#[CoversMethod(BackendUserProcessor::class, 'processLogRecord')]
class BackendUserProcessorTest extends UnitTestCase
{
    protected function setUp(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['cookieName'] = '';
        $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'] = '';
        $GLOBALS['TYPO3_CONF_VARS']['BE']['lockIP'] = '';
        $GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout'] = '';
        $GLOBALS['TYPO3_CONF_VARS']['BE']['lockIP'] = 0;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['lockIPv6'] = 0;
        parent::setUp();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']);
        parent::tearDown();
    }

    public function testBackendUserProcessorAddsBackendUserUidToLogEntryData()
    {
        $expectedBeUserUid = 31;

        // preparation
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->user = ['uid' => $expectedBeUserUid];

        $backendUserProcessor = new BackendUserProcessor();

        $log = new LogRecord('Foo.Bar', LogLevel::DEBUG, 'baz', []);
        $log = $backendUserProcessor->processLogRecord($log);

        $this->assertSame(['be_user' => $expectedBeUserUid], $log->getData());
    }

    public function testBackendUserProcessorAddsUnknownValueStringToLogEntryIfBackendUserIsKnownButHasNoId()
    {
        // preparation
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();

        $backendUserProcessor = new BackendUserProcessor();

        $log = new LogRecord('Foo.Bar', LogLevel::DEBUG, 'baz', []);
        $log = $backendUserProcessor->processLogRecord($log);

        $this->assertSame(['be_user' => 'NO UID'], $log->getData());
    }
}
