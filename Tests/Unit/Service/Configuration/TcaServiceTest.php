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

/**
 * @coversDefaultClass \In2code\In2publishCore\Service\Configuration\TcaService
 */
class TcaServiceTest extends UnitTestCase
{
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
