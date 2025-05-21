<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Utility;

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

use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use In2code\In2publishCore\Tests\UnitTestCase;
use In2code\In2publishCore\Utility\BackendUtility;
use PHPUnit\Framework\Attributes\CoversMethod;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionProperty;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Http\ServerRequest;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
#[CoversMethod(BackendUtility::class, 'getPageIdentifier')]
class BackendUtilityTest extends UnitTestCase
{
    protected array $rows = [];
    protected ?ServerRequestInterface $request = null;

    protected function setUp(): void
    {
        parent::setUp();

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('listTableNames')->willReturn(
            [
                'tt_content',
            ],
        );

        $result = $this->createMock(Result::class);
        $result->method('fetchAssociative')->willReturnCallback(
            function () {
                return $this->rows;
            },
        );

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturn($queryBuilder);
        $queryBuilder->method('from')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturn($queryBuilder);
        $queryBuilder->method('setMaxResults')->willReturn($queryBuilder);
        $queryBuilder->method('executeQuery')->willReturn($result);
        $queryBuilder->method('getRestrictions')->willReturn($this->createMock(DefaultRestrictionContainer::class));

        $expressionBuilder = $this->createMock(\TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder::class);
        $expressionBuilder->method('eq')->willReturn('');
        $queryBuilder->method('expr')->willReturn($expressionBuilder);

        $connection = $this->createMock(Connection::class);
        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $connection->method('createQueryBuilder')->willReturn($queryBuilder);

        $reflection = new ReflectionProperty(ConnectionPool::class, 'connections');
        $reflection->setAccessible(true);
        $reflection->setValue(['Default' => $connection]);

        // Create a base request mock
        $this->request = new ServerRequest();
        $GLOBALS['TYPO3_REQUEST'] = $this->request;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($GLOBALS['TYPO3_REQUEST']);
        $this->request = null;
    }

    public function testGetPageIdentifierReturnsZeroIfNoPidCanBeFound(): void
    {
        $this->assertSame(0, BackendUtility::getPageIdentifier());
    }

    public function testGetPageIdentifierReturnsPidForRollbackRequests(): void
    {
        $expectedPid = 4;
        $table = 'tt_content';
        $uid = 13;

        $this->rows = ['uid' => $uid, 'pid' => $expectedPid];

        $request = $this->request->withQueryParams(['element' => $table . ':' . $uid]);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $this->assertSame($expectedPid, BackendUtility::getPageIdentifier());
    }

    public function testGetPageIdentifierReturnsZeroIfRollbackRequestIsInvalid(): void
    {
        $request = $this->request->withQueryParams(['element' => '13']);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $this->assertSame(0, BackendUtility::getPageIdentifier());
    }

    public function testGetPageIdentifierReturnsZeroIfRollbackRecordDoesNotExist(): void
    {
        $request = $this->request->withQueryParams(['element' => 'tt_content:13']);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $this->assertSame(0, BackendUtility::getPageIdentifier());
    }

    public function testGetPageIdentifierReturnsPidFromRedirectParameter(): void
    {
        $request = $this->request->withQueryParams(['redirect' => 'script.php?param1=a&id=123&param2=2']);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $this->assertSame(123, BackendUtility::getPageIdentifier());
    }

    public function testGetPageIdentifierReturnsZeroIfRedirectParameterDoesNotContainAnId(): void
    {
        $request = $this->request->withQueryParams(['redirect' => 'script.php?param1=a&param2=2']);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $this->assertSame(0, BackendUtility::getPageIdentifier());
    }

    public function testGetPageIdentifierReturnsZeroIfRedirectIdParameterIsEmpty(): void
    {
        $request = $this->request->withQueryParams(['redirect' => 'script.php?param1=a&id=&param2=2']);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $this->assertSame(0, BackendUtility::getPageIdentifier());
    }

    public function testGetPageIdentifierReturnsPidFromPopViewId(): void
    {
        $request = $this->request->withQueryParams(['popViewId' => '123']);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $this->assertSame(123, BackendUtility::getPageIdentifier());
    }

    public function testGetPageIdentifierReturnsPidFromCmdParameter(): void
    {
        $request = $this->request->withQueryParams(['cmd' => ['pages' => ['6543' => ['delete' => '1']]]]);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $this->assertSame(6543, BackendUtility::getPageIdentifier());
    }

    public function testGetPageIdentifierReturnsZeroIfCmdParameterIsEmpty(): void
    {
        $request = $this->request->withQueryParams(['cmd' => []]);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $this->assertSame(0, BackendUtility::getPageIdentifier());

        $request = $this->request->withQueryParams(['cmd' => ['pages' => []]]);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $this->assertSame(0, BackendUtility::getPageIdentifier());
    }

    public function testGetPageIdentifierReturnsPidFromIdParameter(): void
    {
        $request = $this->request->withQueryParams(['id' => 321]);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $this->assertSame(321, BackendUtility::getPageIdentifier());
    }

    public function testGetPageIdentifierReturnsPidFromPAjaxPageId(): void
    {
        $request = $this->request->withQueryParams(['pageId' => 321]);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $this->assertSame(321, BackendUtility::getPageIdentifier());
    }

    public function testGetPageIdentifierReturnsPidFromArgumentsIfTableIsPages(): void
    {
        $this->assertSame(321, BackendUtility::getPageIdentifier(321, 'pages'));
    }

    public function testGetPageIdentifierReturnsZeroIfAnyMethodFails(): void
    {
        $this->rows = ['uid' => 321];
        $this->assertSame(0, BackendUtility::getPageIdentifier(321, 'tt_content'));
    }
}
