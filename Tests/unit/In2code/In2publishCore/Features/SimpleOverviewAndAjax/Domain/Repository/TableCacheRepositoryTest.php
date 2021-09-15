<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\In2code\In2publishCore\Features\SimpleOverviewAndAjax\Domain\Repository;

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
use In2code\In2publishCore\Features\SimpleOverviewAndAjax\Domain\Repository\TableCacheRepository;
use In2code\In2publishCore\Tests\UnitTester;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass \In2code\In2publishCore\Features\SimpleOverviewAndAjax\Domain\Repository\TableCacheRepository
 */
class TableCacheRepositoryTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before(): void
    {
        $this->tester->setUp();
    }

    protected function _after(): void
    {
        $this->tester->tearDown();
    }

    protected function getTableCacheRepositoryInstance(): TableCacheRepository
    {
        /** @var TableCacheRepository|MockObject $mock */
        $mock = $this
            ->getMockBuilder(TableCacheRepository::class)
            ->setMethods(['getConnection'])
            ->disableOriginalConstructor()
            ->getMock();

        $mock->method('getConnection')->willReturn(null);

        return $mock;
    }

    /**
     * @covers ::findByUid
     */
    public function testFindByUidReturnsEmptyArrayIfDatabaseIsNotConnected(): void
    {
        $tableCacheRepository = $this->getTableCacheRepositoryInstance();
        $this->assertSame([], $tableCacheRepository->findByUid('pages', 31));
    }

    /**
     * @covers ::findByPid
     */
    public function testFindByPidReturnsEmptyArrayIfDatabaseIsNotConnected(): void
    {
        $tableCacheRepository = $this->getTableCacheRepositoryInstance();
        $this->assertSame([], $tableCacheRepository->findByPid('pages', 31));
    }
}
