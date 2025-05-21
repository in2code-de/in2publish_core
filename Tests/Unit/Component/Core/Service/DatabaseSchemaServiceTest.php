<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Service;

use In2code\In2publishCore\Component\Core\Service\Database\DatabaseSchemaService;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Connection;

use function method_exists;

#[CoversMethod(DatabaseSchemaService::class, 'injectCache')]
#[CoversMethod(DatabaseSchemaService::class, 'getColumnNames')]
#[CoversMethod(DatabaseSchemaService::class, 'getTableNames')]
class DatabaseSchemaServiceTest extends UnitTestCase
{
    public function testInjectCachePopulatesColumnsAndTables(): void
    {
        $localDatbase = $this->createMock(Connection::class);

        $localDatbase->expects($this->never())->method('getSchemaInformation');

        $cacheData = [
            'columns' => [
                'tableFoo' => [
                    'col1',
                    'col2',
                ],
            ],
            'tables' => [
                'tableFoo',
            ],
        ];

        $cache = $this->createMock(FrontendInterface::class);
        $cache->expects($this->once())->method('get')->willReturn($cacheData);

        $databaseSchemaService = new DatabaseSchemaService();
        $databaseSchemaService->injectLocalDatabase($localDatbase);
        $databaseSchemaService->injectCache($cache);

        $this->assertSame(['tableFoo'], $databaseSchemaService->getTableNames());
        $this->assertSame(['col1', 'col2'], $databaseSchemaService->getColumnNames('tableFoo'));
    }
}
