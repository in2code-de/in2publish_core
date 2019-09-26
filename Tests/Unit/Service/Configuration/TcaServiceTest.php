<?php
namespace In2code\In2publishCore\Tests\Unit\Service\Configuration;

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

use In2code\In2publishCore\Service\Configuration\TcaService;
use PHPUnit_Framework_MockObject_MockObject;
use stdClass;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * @coversDefaultClass \In2code\In2publishCore\Service\Configuration\TcaService
 */
class TcaServiceTest extends UnitTestCase
{
    /**
     * @covers ::__construct
     * @covers ::getAllTableNames
     * @covers ::getTca
     */
    public function testGetAllTableNamesReturnsAllTableNames()
    {
        $this->setTca(
            [
                'pages' => [],
                'fe_users' => [],
                'be_users' => [],
                'tt_content' => [],
            ]
        );

        $tcaService = new TcaService();
        $this->assertSame(['pages', 'fe_users', 'be_users', 'tt_content'], $tcaService->getAllTableNames());
    }

    /**
     * @covers ::__construct
     * @covers ::getAllTableNames
     * @covers ::getTca
     */
    public function testGetAllTableNamesReturnsAllTableNamesExceptExcludedTableNames()
    {
        $this->setTca(
            [
                'pages' => [],
                'fe_users' => [],
                'be_users' => [],
                'tt_content' => [],
            ]
        );

        $tcaService = new TcaService();
        $this->assertSame(
            ['fe_users', 'be_users', 'tt_content'],
            // Get array_values, because we don't want to test the array keys!
            array_values($tcaService->getAllTableNames(['pages', 'cf_cache']))
        );
    }

    /**
     * @covers ::getAllTableNamesAllowedOnRootLevel
     */
    public function testGetAllTableNamesAllowedOnRootLevelReturnsAllConfiguredTables()
    {
        $this->setTca(
            [
                'pages' => [
                    'ctrl' => [
                        'rootLevel' => true,
                    ],
                ],
                'fe_users' => [
                    'ctrl' => [
                        'rootLevel' => false,
                    ],
                ],
                'be_users' => [
                    'ctrl' => [
                        'rootLevel' => 1,
                    ],
                ],
                'tt_content' => [
                    'ctrl' => [
                        'rootLevel' => -1,
                    ],
                ],
            ]
        );
        $tcaService = new TcaService();
        $this->assertSame(
            ['pages', 'be_users', 'tt_content'],
            // Get array_values, because we don't want to test the array keys!
            array_values($tcaService->getAllTableNamesAllowedOnRootLevel())
        );
    }

    /**
     * @covers ::getAllTableNamesAllowedOnRootLevel
     */
    public function testPagesTableIsAlwaysAddedToRootLevelTables()
    {
        $this->setTca(['fe_users' => ['ctrl' => ['rootLevel' => true]]]);

        $tcaService = new TcaService();
        $this->assertSame(
            ['fe_users', 'pages'],
            // Get array_values, because we don't want to test the array keys!
            array_values($tcaService->getAllTableNamesAllowedOnRootLevel(['pages']))
        );
    }

    /**
     * @covers ::getAllTableNamesAllowedOnRootLevel
     */
    public function testGetAllTableNamesAllowedOnRootLevelReturnesAllRootLevelTablesExceptExcludedTables()
    {
        $this->setTca(['fe_users' => ['ctrl' => ['rootLevel' => true]]]);

        $tcaService = new TcaService();
        $this->assertSame(
            ['fe_users', 'pages'],
            // Get array_values, because we don't want to test the array keys!
            array_values($tcaService->getAllTableNamesAllowedOnRootLevel())
        );
    }

    /**
     * @covers ::getLabelFieldFromTable
     */
    public function testGetLabelFieldFromTableReturnsEmptyStringIfFieldIsNotSet()
    {
        $this->setTca([]);

        $tcaService = new TcaService();
        $this->assertSame('', $tcaService->getLabelFieldFromTable('foo'));
    }

    /**
     * @covers ::getLabelFieldFromTable
     */
    public function testGetLabelFieldFromTableReturnsConfiguredFieldName()
    {
        $this->setTca(['bar' => ['ctrl' => ['label' => 'baz']]]);

        $tcaService = new TcaService();
        $this->assertSame('baz', $tcaService->getLabelFieldFromTable('bar'));
    }

    /**
     * @covers ::getLabelAltFieldFromTable
     */
    public function testGetLabelAltFieldFromTableReturnsEmptyStringIfFieldIsNotSet()
    {
        $this->setTca([]);

        $tcaService = new TcaService();
        $this->assertSame('', $tcaService->getLabelAltFieldFromTable('foo'));
    }

    /**
     * @covers ::getLabelAltFieldFromTable
     */
    public function testGetLabelAltFieldFromTableReturnsConfiguredFieldName()
    {
        $this->setTca(['bar' => ['ctrl' => ['label_alt' => 'baz']]]);

        $tcaService = new TcaService();
        $this->assertSame('baz', $tcaService->getLabelAltFieldFromTable('bar'));
    }

    /**
     * @covers ::getTitleFieldFromTable
     */
    public function testGetTitleFieldReturnsEmptyStringIfNotConfigured()
    {
        $this->setTca([]);

        $tcaService = new TcaService();
        $this->assertSame('', $tcaService->getTitleFieldFromTable('foo'));
    }

    /**
     * @covers ::getTitleFieldFromTable
     */
    public function testGetTitleFieldReturnsConfiguredFieldName()
    {
        $this->setTca(['boo' => ['ctrl' => ['title' => 'baez']]]);

        $tcaService = new TcaService();
        $this->assertSame('baez', $tcaService->getTitleFieldFromTable('boo'));
    }

    /**
     * @covers ::getSortingField
     */
    public function testGetSortingFieldReturnsSortByFieldName()
    {
        $this->setTca(['bar' => ['ctrl' => ['sortby' => 'boo']]]);

        $tcaService = new TcaService();
        $this->assertSame('boo', $tcaService->getSortingField('bar'));
    }

    /**
     * @covers ::getSortingField
     */
    public function testGetSortingFieldUsesCrdateFieldAsFallback()
    {
        $this->setTca(['faz' => ['ctrl' => ['sortby' => '', 'crdate' => 'bam']]]);

        $tcaService = new TcaService();
        $this->assertSame('bam', $tcaService->getSortingField('faz'));
    }

    /**
     * @covers ::getSortingField
     */
    public function testGetSortingFieldReturnsEmptyStringIfFieldIsNotSet()
    {
        $this->setTca([]);

        $tcaService = new TcaService();
        $this->assertSame('', $tcaService->getSortingField('faz'));
    }

    /**
     * @covers ::getDeletedField
     */
    public function testGetDeletedFieldReturnsEmptyStringIfFieldIsNotSet()
    {
        $this->setTca([]);

        $tcaService = new TcaService();
        $this->assertSame('', $tcaService->getDeletedField('foo'));
    }

    /**
     * @covers ::getDeletedField
     */
    public function testGetDeletedFieldReturnsConfiguredField()
    {
        $this->setTca(['fey' => ['ctrl' => ['delete' => 'bou']]]);

        $tcaService = new TcaService();
        $this->assertSame('bou', $tcaService->getDeletedField('fey'));
    }

    /**
     * @covers ::getAllTableNamesWithPidAndUidField
     */
    public function testGetAllTableNamesWithPidAndUidFieldReturnsExpectedTables()
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|TcaService $tcaService */
        $tcaService = $this->getMockBuilder(TcaService::class)->setMethods(['getDatabaseSchema'])->getMock();

        $databaseSchema = [
            'foo' => [
                'pid' => [],
                'uid' => [],
            ],
            'bar' => [
                'pid' => [],
                'uid' => [],
            ],
            'baz' => [
                'tstamp' => [],
            ],
            'boo' => [
                'pid' => [],
            ],
            'far' => [
                'uid' => [],
            ],
        ];

        $tcaService->method('getDatabaseSchema')->will($this->returnValue($databaseSchema));

        // ignore array keys
        $this->assertSame(['foo', 'bar'], array_values($tcaService->getAllTableNamesWithPidAndUidField()));
    }

    /**
     * @covers ::getAllTableNamesWithPidAndUidField
     * @depends testGetAllTableNamesWithPidAndUidFieldReturnsExpectedTables
     */
    public function testGetAllTableNamesWithPidAndUidFieldReturnsExpectedTablesExceptExcludedTables()
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|TcaService $tcaService */
        $tcaService = $this->getMockBuilder(TcaService::class)->setMethods(['getDatabaseSchema'])->getMock();

        $databaseSchema = [
            'foo' => [
                'pid' => [],
                'uid' => [],
            ],
            'bar' => [
                'pid' => [],
                'uid' => [],
            ],
            'baz' => [
                'uid' => [],
            ],
            'faz' => [
                'pid' => [],
            ],
            'boo' => [
                'pid' => [],
                'uid' => [],
            ],
        ];

        $excludedTables = [
            'bar',
        ];

        $tcaService->method('getDatabaseSchema')->will($this->returnValue($databaseSchema));

        // ignore array keys
        $this->assertSame(
            ['foo', 'boo'],
            array_values($tcaService->getAllTableNamesWithPidAndUidField($excludedTables))
        );
    }

    /**
     * @covers ::getTableLabel
     */
    public function testGetTableLabelReturnsUpperCaseFirstTableNameIfTitleFieldIsNotSet()
    {
        $this->setTca([]);

        $tcaService = new TcaService();
        $this->assertSame('Foo', $tcaService->getTableLabel('foo'));
    }

    /**
     * @covers ::getTableLabel
     */
    public function testGetTableLabelReturnsTranslatedLabel()
    {
        $this->setTca(['foo' => ['ctrl' => ['title' => 'bar']]]);

        /** @var PHPUnit_Framework_MockObject_MockObject|TcaService $tcaService */
        $tcaService = $this->getMockBuilder(TcaService::class)->setMethods(['localizeLabel'])->getMock();
        $tcaService->method('localizeLabel')->will($this->returnValue('bazinga'));

        $this->assertSame('bazinga', $tcaService->getTableLabel('foo'));
    }

    /**
     * @covers ::getConfigurationArrayForTable
     */
    public function testGetConfigurationArrayForTableReturnsNullIfTableIsNotConfigured()
    {
        $this->setTca([]);

        $tcaService = new TcaService();
        $this->assertNull($tcaService->getConfigurationArrayForTable('foo'));
    }

    /**
     * @covers ::getConfigurationArrayForTable
     */
    public function testGetConfigurationArrayForTableReturnsConfiguredTable()
    {
        $expected = [
            'ctrl' => [
                'delete' => 'whee',
            ],
            'same' => new stdClass(),
        ];
        $this->setTca(['foo' => $expected]);

        $tcaService = new TcaService();
        $this->assertSame($expected, $tcaService->getConfigurationArrayForTable('foo'));
    }

    /**
     * @covers ::getColumnConfigurationForTableColumn
     */
    public function testGetColumnConfigurationForTableColumnReturnsNullForMissingTable()
    {
        $tcaService = new TcaService();
        $this->assertNull($tcaService->getColumnConfigurationForTableColumn('foo', 'bar'));
    }

    /**
     * @covers ::getColumnConfigurationForTableColumn
     */
    public function testGetColumnConfigurationForTableColumnReturnsColumnConfiguration()
    {
        $expected = [
            'columns' => [
                'bar' => ['whee'],
            ],
        ];
        $this->setTca(['table' => $expected]);

        $tcaService = new TcaService();
        $this->assertSame(['whee'], $tcaService->getColumnConfigurationForTableColumn('table', 'bar'));
    }

    /**
     * @covers ::getColumnConfigurationForTableColumn
     * @depends testGetColumnConfigurationForTableColumnReturnsColumnConfiguration
     */
    public function testGetColumnConfigurationForTableColumnReturnsNullForMissingColumn()
    {
        $expected = [
            'columns' => [
                'boo' => ['whee'],
            ],
        ];
        $this->setTca(['table' => $expected]);

        $tcaService = new TcaService();
        $this->assertNull($tcaService->getColumnConfigurationForTableColumn('table', 'bar'));
    }

    /**
     * @covers ::isHiddenRootTable
     */
    public function testIsHiddenRootTableReturnsTrueForInvisibleTablesPossibleOnRoot()
    {
        $this->setTca(['table' => ['ctrl' => ['hideTable' => true, 'rootLevel' => 1]]]);
        $tcaService = new TcaService();
        $this->assertTrue($tcaService->isHiddenRootTable('table'));

        $this->setTca(['table' => ['ctrl' => ['hideTable' => 1, 'rootLevel' => -1]]]);
        $tcaService = new TcaService();
        $this->assertTrue($tcaService->isHiddenRootTable('table'));
    }

    /**
     * @covers ::isHiddenRootTable
     */
    public function testIsHiddenRootTableReturnsFalseForVisibleOrPagesOnlyTables()
    {
        $this->setTca(['table' => ['ctrl' => ['hideTable' => false, 'rootLevel' => 1]]]);
        $tcaService = new TcaService();
        $this->assertFalse($tcaService->isHiddenRootTable('table'));

        $this->setTca(['table' => ['ctrl' => ['hideTable' => 1, 'rootLevel' => 0]]]);
        $tcaService = new TcaService();
        $this->assertFalse($tcaService->isHiddenRootTable('table'));
        $this->assertFalse($tcaService->isHiddenRootTable('foo'));
    }

    /**
     * @param array $tca
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    protected function setTca(array $tca = [])
    {
        $GLOBALS['TCA'] = $tca;
    }

    /**
     * @param mixed $languageService
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    protected function setLanguageService($languageService)
    {
        $GLOBALS['LANG'] = $languageService;
    }
}
