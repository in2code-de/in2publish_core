<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\In2code\In2publishCore\Utility;

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

use Codeception\Test\Unit;
use In2code\In2publishCore\Tests\UnitTester;
use In2code\In2publishCore\Utility\BackendUtility;

/**
 * @coversDefaultClass \In2code\In2publishCore\Utility\BackendUtility
 */
class BackendUtilityTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $this->tester->setUp();
    }

    protected function _after()
    {
        $this->tester->tearDown();
    }

    /**
     * @covers ::getPageIdentifier
     *
     * @SuppressWarnings("PHPMD.Superglobals")
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testGetPageIdentifierReturnsZeroIfNoPidCanBeFound()
    {
        // assure there are no values to get a pid from
        $_POST = [];
        $_GET = [];

        $this->assertSame(0, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     *
     * @SuppressWarnings("PHPMD.Superglobals")
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testGetPageIdentifierReturnsPidForRollbackRequests()
    {
        $expectedPid = 4;
        $table = 'tt_content';
        $uid = 13;

        $this->tester->haveInDatabase($table, ['uid' => $uid, 'pid' => $expectedPid]);

        $_POST['element'] = '' . $table . ':' . $uid . '';

        $this->assertSame($expectedPid, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     *
     * @SuppressWarnings("PHPMD.Superglobals")
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testGetPageIdentifierReturnsZeroIfRollbackRequestIsInvalid()
    {
        $_POST['element'] = '13';

        $this->assertSame(0, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     *
     * @SuppressWarnings("PHPMD.Superglobals")
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testGetPageIdentifierReturnsZeroIfRollbackRecordDoesNotExist()
    {
        $_POST['element'] = 'tt_content:13';

        $this->assertSame(0, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     *
     * @SuppressWarnings("PHPMD.Superglobals")
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testGetPageIdentifierReturnsPidFromRecordInDataValues()
    {
        $expectedPid = 14;
        $this->tester->haveInDatabase('tt_content', ['uid' => 16, 'pid' => $expectedPid]);

        $_POST['data']['tt_content']['16'] = 'bar';

        $this->assertSame($expectedPid, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     *
     * @SuppressWarnings("PHPMD.Superglobals")
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testGetPageIdentifierReturnsZeroIfTheRecordCanNotBeFound()
    {
        $this->tester->cantSeeInDatabase('tt_content', ['uid' => 16]);

        $_POST['data']['tt_content']['16'] = 'bar';

        $this->assertSame(0, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     *
     * @SuppressWarnings("PHPMD.Superglobals")
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testGetPageIdentifierReturnsZeroIfTheRecordCanBeFound()
    {
        $this->tester->haveInDatabase('tt_content', ['uid' => 321, 'pid' => 2]);

        $this->assertSame(2, BackendUtility::getPageIdentifier(321, 'tt_content'));
    }

    /**
     * @covers ::getPageIdentifier
     *
     * @SuppressWarnings("PHPMD.Superglobals")
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testGetPageIdentifierReturnsPidFromRedirectParameter()
    {
        $_POST['redirect'] = 'script.php?param1=a&id=123&param2=2';

        $this->assertSame(123, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     *
     * @SuppressWarnings("PHPMD.Superglobals")
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testGetPageIdentifierReturnsZeroIfRedirectParameterDoesNotContainAnId()
    {
        $_POST['redirect'] = 'script.php?param1=a&param2=2';

        $this->assertSame(0, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     *
     * @SuppressWarnings("PHPMD.Superglobals")
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testGetPageIdentifierReturnsZeroIfRedirectIdParameterIsEmpty()
    {
        $_POST['redirect'] = 'script.php?param1=a&id=&param2=2';

        $this->assertSame(0, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     *
     * @SuppressWarnings("PHPMD.Superglobals")
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testGetPageIdentifierReturnsPidFromPopViewId()
    {
        $_POST['popViewId'] = '123';

        $this->assertSame(123, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     *
     * @SuppressWarnings("PHPMD.Superglobals")
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testGetPageIdentifierReturnsPidFromCmdParameter()
    {
        $_POST['cmd']['pages']['6543']['delete'] = '1';

        $this->assertSame(6543, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     *
     * @SuppressWarnings("PHPMD.Superglobals")
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testGetPageIdentifierReturnsZeroIfCmdParameterIsEmpty()
    {
        $_POST['cmd'] = [];

        $this->assertSame(0, BackendUtility::getPageIdentifier());

        $_POST['cmd']['pages'] = [];

        $this->assertSame(0, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     *
     * @SuppressWarnings("PHPMD.Superglobals")
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testGetPageIdentifierReturnsPidFromIdParameter()
    {
        $_POST['id'] = 321;

        $this->assertSame(321, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     *
     * @SuppressWarnings("PHPMD.Superglobals")
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testGetPageIdentifierReturnsPidFromPAjaxPageId()
    {
        $_POST['pageId'] = 321;

        $this->assertSame(321, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     *
     * @SuppressWarnings("PHPMD.Superglobals")
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testGetPageIdentifierReturnsPidFromArgumentsIfTableIsPages()
    {
        $this->assertSame(321, BackendUtility::getPageIdentifier(321, 'pages'));
    }

    /**
     * @covers ::getPageIdentifier
     *
     * @SuppressWarnings("PHPMD.Superglobals")
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testGetPageIdentifierReturnsZeroIfAnyMethodFails()
    {
        $this->tester->cantSeeInDatabase('tt_content', ['uid' => 321]);

        $this->assertSame(0, BackendUtility::getPageIdentifier(321, 'tt_content'));
    }
}
