<?php
namespace In2code\In2publishCore\Tests\Unit\Service\Database;

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

use In2code\In2publishCore\Service\Database\DatabaseSchemaService;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * @coversDefaultClass \In2code\In2publishCore\Service\Database\DatabaseSchemaService
 */
class DatabaseSchemaServiceTest extends UnitTestCase
{
    /**
     * @covers ::__construct
     * @covers ::getDatabaseSchema
     * @covers ::getDatabase
     */
    public function testGetDatabaseSchemaBuildArrayOfTableAndFieldInformation()
    {
        $this->buildDatabaseMock();

        $expected = [
            'foo' => [
                '_TABLEINFO' => [],
                'faz' => ['name' => 'faz'],
            ],
            'bar' => [
                '_TABLEINFO' => [],
                'raz' => ['name' => 'naz'],
            ],
        ];

        /** @var VariableFrontend|\PHPUnit_Framework_MockObject_MockObject $cacheFrontend */
        $cacheFrontend = $this->getMockBuilder(VariableFrontend::class)
                              ->setMethods(['has', 'get', 'set'])
                              ->disableOriginalConstructor()
                              ->getMock();
        // set return value to true to execute the cache->get() lines
        $cacheFrontend->method('has')->willReturn(true);
        $cacheFrontend->method('get')->willReturn(false);

        /** @var DatabaseSchemaService|\PHPUnit_Framework_MockObject_MockObject $schemaService */
        $schemaService = $this->getMockBuilder(DatabaseSchemaService::class)
                              ->setMethods(['getCache'])
                              ->disableOriginalConstructor()
                              ->getMock();
        $schemaService->method('getCache')->willReturn($cacheFrontend);
        $schemaService->__construct();

        $this->assertSame($expected, $schemaService->getDatabaseSchema());
    }

    /**
     * @covers ::tableExists
     */
    public function testTableExistsReturnsTrueIfTableExistsInSchema()
    {
        /** @var VariableFrontend|\PHPUnit_Framework_MockObject_MockObject $cacheFrontend */
        $cacheFrontend = $this->getMockBuilder(VariableFrontend::class)
                              ->setMethods(['has', 'get', 'set'])
                              ->disableOriginalConstructor()
                              ->getMock();
        $cacheFrontend->method('has')->willReturn(false);

        /** @var DatabaseSchemaService|\PHPUnit_Framework_MockObject_MockObject $schemaService */
        $schemaService = $this->getMockBuilder(DatabaseSchemaService::class)
                              ->setMethods(['getCache', 'getDatabaseSchema'])
                              ->disableOriginalConstructor()
                              ->getMock();
        $schemaService->method('getCache')->willReturn($cacheFrontend);
        $schemaService->method('getDatabaseSchema')->willReturn(['foo' => []]);
        $schemaService->__construct();
        $this->assertTrue($schemaService->tableExists('foo'));
    }

    /**
     * @covers ::tableExists
     */
    public function testTableExistsReturnsFalseIfTableDoesNotExist()
    {
        /** @var VariableFrontend|\PHPUnit_Framework_MockObject_MockObject $cacheFrontend */
        $cacheFrontend = $this->getMockBuilder(VariableFrontend::class)
                              ->setMethods(['has', 'get', 'set'])
                              ->disableOriginalConstructor()
                              ->getMock();
        $cacheFrontend->method('has')->willReturn(false);

        /** @var DatabaseSchemaService|\PHPUnit_Framework_MockObject_MockObject $schemaService */
        $schemaService = $this->getMockBuilder(DatabaseSchemaService::class)
                              ->setMethods(['getCache', 'getDatabaseSchema'])
                              ->disableOriginalConstructor()
                              ->getMock();
        $schemaService->method('getCache')->willReturn($cacheFrontend);
        $schemaService->method('getDatabaseSchema')->willReturn(['boo' => []]);
        $schemaService->__construct();
        $this->assertFalse($schemaService->tableExists('foo'));
    }

    /**
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    protected function buildDatabaseMock()
    {
        $database = $this->getMockBuilder(DatabaseConnection::class)->setMethods(
            ['admin_get_tables', 'admin_get_fields']
        )->getMock();

        $database
            ->expects($this->once())
            ->method('admin_get_tables')
            ->willReturn(['foo' => [], 'bar' => []]);

        $database
            ->expects($this->exactly(2))
            ->method('admin_get_fields')
            ->withConsecutive(['foo'], ['bar'])
            ->willReturnOnConsecutiveCalls(['faz' => ['name' => 'faz']], ['raz' => ['name' => 'naz']]);

        $GLOBALS['TYPO3_DB'] = $database;
    }
}
