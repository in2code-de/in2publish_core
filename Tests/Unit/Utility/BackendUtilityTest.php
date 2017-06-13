<?php
namespace In2code\In2publishCore\Tests\Unit\Utility;

/***************************************************************
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
 ***************************************************************/

use In2code\In2publishCore\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * @coversDefaultClass \In2code\In2publishCore\Utility\BackendUtility
 */
class BackendUtilityTest extends UnitTestCase
{
    /**
     * @covers ::getPageIdentifier
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function testGetPageIdentifierReturnsZeroIfNoPidCanBeFound()
    {
        // assure there are no values to get a pid from
        $_POST = array();
        $_GET = array();

        $this->assertSame(0, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function testGetPageIdentifierReturnsPidForRollbackRequests()
    {
        /** @var DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseMock */
        $databaseMock = $this->getMockBuilder(DatabaseConnection::class)
                             ->setMethods(['exec_SELECTgetSingleRow', 'isConnected', 'connectDB'])
                             ->getMock();

        $databaseMock
            ->expects($this->any())
            ->method('isConnected')
            ->willReturn(true);

        $databaseMock
            ->expects($this->any())
            ->method('connectDB')
            ->willReturn(true);

        $expectedPid = 4;

        /**
         * @see \TYPO3\CMS\Core\Database\DatabaseConnection::exec_SELECTgetSingleRow
         */
        $databaseMock->expects($this->once())
                     ->method('exec_SELECTgetSingleRow')
                     ->with('pid', 'tt_content', 'uid=13')
                     ->willReturn(array('pid' => $expectedPid));

        $_POST['element'] = 'tt_content:13';
        $GLOBALS['TYPO3_DB'] = $databaseMock;

        $this->assertSame($expectedPid, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function testGetPageIdentifierReturnsZeroIfRollbackRequestIsInvalid()
    {
        $_POST['element'] = '13';

        $this->assertSame(0, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function testGetPageIdentifierReturnsZeroIfRollbackRecordDoesNotExist()
    {
        /** @var DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseMock */
        $databaseMock = $this->getMockBuilder(DatabaseConnection::class)
                             ->setMethods(['exec_SELECTgetSingleRow', 'isConnected', 'connectDB'])
                             ->getMock();

        $databaseMock
            ->expects($this->any())
            ->method('isConnected')
            ->willReturn(true);

        $databaseMock
            ->expects($this->any())
            ->method('connectDB')
            ->willReturn(true);

        /**
         * @see \TYPO3\CMS\Core\Database\DatabaseConnection::exec_SELECTgetSingleRow
         */
        $databaseMock->expects($this->once())
                     ->method('exec_SELECTgetSingleRow')
                     ->with('pid', 'tt_content', 'uid=13')
                     ->willReturn(false);

        $_POST['element'] = 'tt_content:13';
        $GLOBALS['TYPO3_DB'] = $databaseMock;

        $this->assertSame(0, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function testGetPageIdentifierReturnsPidFromRecordInDataValues()
    {
        /** @var DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseMock */
        $databaseMock = $this->getMockBuilder(DatabaseConnection::class)
                             ->setMethods(['exec_SELECTgetSingleRow', 'isConnected', 'connectDB'])
                             ->getMock();

        $databaseMock
            ->expects($this->any())
            ->method('isConnected')
            ->willReturn(true);

        $databaseMock
            ->expects($this->any())
            ->method('connectDB')
            ->willReturn(true);

        $expectedPid = 14;

        /**
         * @see \TYPO3\CMS\Core\Database\DatabaseConnection::exec_SELECTgetSingleRow
         */
        $databaseMock->expects($this->once())
                     ->method('exec_SELECTgetSingleRow')
                     ->with('pid', 'tt_content', 'uid=16')
                     ->willReturn(array('pid' => 14));

        $_POST['data']['tt_content']['16'] = 'bar';
        $GLOBALS['TYPO3_DB'] = $databaseMock;

        $this->assertSame($expectedPid, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function testGetPageIdentifierReturnsZeroIfTheRecordCanNotBeFound()
    {
        /** @var DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseMock */
        $databaseMock = $this->getMockBuilder(DatabaseConnection::class)
                             ->setMethods(['exec_SELECTgetSingleRow', 'isConnected', 'connectDB'])
                             ->getMock();

        $databaseMock
            ->expects($this->any())
            ->method('isConnected')
            ->willReturn(true);

        $databaseMock
            ->expects($this->any())
            ->method('connectDB')
            ->willReturn(true);

        /**
         * @see \TYPO3\CMS\Core\Database\DatabaseConnection::exec_SELECTgetSingleRow
         */
        $databaseMock->expects($this->once())
                     ->method('exec_SELECTgetSingleRow')
                     ->with('pid', 'tt_content', 'uid=16')
                     ->willReturn(false);

        $_POST['data']['tt_content']['16'] = 'bar';
        $GLOBALS['TYPO3_DB'] = $databaseMock;

        $this->assertSame(0, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function testGetPageIdentifierReturnsZeroIfTheRecordCanBeFound()
    {
        /** @var DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseMock */
        $databaseMock = $this->getMockBuilder(DatabaseConnection::class)
                             ->setMethods(['exec_SELECTgetSingleRow', 'isConnected', 'connectDB'])
                             ->getMock();

        $databaseMock
            ->expects($this->any())
            ->method('isConnected')
            ->willReturn(true);

        $databaseMock
            ->expects($this->any())
            ->method('connectDB')
            ->willReturn(true);

        /**
         * @see \TYPO3\CMS\Core\Database\DatabaseConnection::exec_SELECTgetSingleRow
         */
        $databaseMock->expects($this->once())
                     ->method('exec_SELECTgetSingleRow')
                     ->with('pid', 'tt_content', 'uid=321')
                     ->willReturn(['pid' => '2']);

        $GLOBALS['TYPO3_DB'] = $databaseMock;

        $this->assertSame(2, BackendUtility::getPageIdentifier(321, 'tt_content'));
    }

    /**
     * @covers ::getPageIdentifier
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function testGetPageIdentifierReturnsPidFromRedirectParameter()
    {
        $_POST['redirect'] = 'script.php?param1=a&id=123&param2=2';

        $this->assertSame(123, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function testGetPageIdentifierReturnsZeroIfRedirectParameterDoesNotContainAnId()
    {
        $_POST['redirect'] = 'script.php?param1=a&param2=2';

        $this->assertSame(0, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function testGetPageIdentifierReturnsZeroIfRedirectIdParameterIsEmpty()
    {
        $_POST['redirect'] = 'script.php?param1=a&id=&param2=2';

        $this->assertSame(0, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function testGetPageIdentifierReturnsPidFromPopViewId()
    {
        $_POST['popViewId'] = '123';

        $this->assertSame(123, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function testGetPageIdentifierReturnsPidFromCmdParameter()
    {
        $_POST['cmd']['pages']['6543']['delete'] = '1';

        $this->assertSame(6543, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function testGetPageIdentifierReturnsZeroIfCmdParameterIsEmpty()
    {
        $_POST['cmd'] = array();

        $this->assertSame(0, BackendUtility::getPageIdentifier());

        $_POST['cmd']['pages'] = array();

        $this->assertSame(0, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function testGetPageIdentifierReturnsPidFromIdParameter()
    {
        $_POST['id'] = 321;

        $this->assertSame(321, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function testGetPageIdentifierReturnsPidFromPAjaxPageId()
    {
        $_POST['pageId'] = 321;

        $this->assertSame(321, BackendUtility::getPageIdentifier());
    }

    /**
     * @covers ::getPageIdentifier
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function testGetPageIdentifierReturnsPidFromArgumentsIfTableIsPages()
    {
        $this->assertSame(321, BackendUtility::getPageIdentifier(321, 'pages'));
    }

    /**
     * @covers ::getPageIdentifier
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function testGetPageIdentifierReturnsZeroIfAnyMethodFails()
    {
        /** @var DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseMock */
        $databaseMock = $this->getMockBuilder(DatabaseConnection::class)
                             ->setMethods(['exec_SELECTgetSingleRow', 'isConnected', 'connectDB'])
                             ->getMock();

        $databaseMock
            ->expects($this->any())
            ->method('isConnected')
            ->willReturn(true);

        $databaseMock
            ->expects($this->any())
            ->method('connectDB')
            ->willReturn(true);

        /**
         * @see \TYPO3\CMS\Core\Database\DatabaseConnection::exec_SELECTgetSingleRow
         */
        $databaseMock->expects($this->once())
                     ->method('exec_SELECTgetSingleRow')
                     ->with('pid', 'tt_content', 'uid=321')
                     ->willReturn(false);

        $GLOBALS['TYPO3_DB'] = $databaseMock;

        $this->assertSame(0, BackendUtility::getPageIdentifier(321, 'tt_content'));
    }
}
