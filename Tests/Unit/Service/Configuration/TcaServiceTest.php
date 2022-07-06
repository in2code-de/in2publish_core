<?php

declare(strict_types=1);

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
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass \In2code\In2publishCore\Service\Configuration\TcaService
 */
class TcaServiceTest extends UnitTestCase
{
    /**
     * @covers ::getAllTableNamesAllowedOnRootLevel
     */
    public function testGetAllTableNamesAllowedOnRootLevelReturnsAllConfiguredTables(): void
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
    public function testPagesTableIsAlwaysAddedToRootLevelTables(): void
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
    public function testGetAllTableNamesAllowedOnRootLevelReturnesAllRootLevelTablesExceptExcludedTables(): void
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
     * @covers ::getTableLabel
     */
    public function testGetTableLabelReturnsUpperCaseFirstTableNameIfTitleFieldIsNotSet(): void
    {
        $this->setTca([]);

        $tcaService = new TcaService();
        $this->assertSame('Foo', $tcaService->getTableLabel('foo'));
    }

    /**
     * @covers ::isHiddenRootTable
     */
    public function testIsHiddenRootTableReturnsTrueForInvisibleTablesPossibleOnRoot(): void
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
    public function testIsHiddenRootTableReturnsFalseForVisibleOrPagesOnlyTables(): void
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
     */
    protected function setTca(array $tca = []): void
    {
        $GLOBALS['TCA'] = $tca;
    }

    /**
     * @param mixed $languageService
     */
    protected function setLanguageService($languageService): void
    {
        $GLOBALS['LANG'] = $languageService;
    }
}
