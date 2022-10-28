<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Service;

use In2code\In2publishCore\Component\Core\Service\Database\DatabaseSchemaService;
use In2code\In2publishCore\Tests\UnitTestCase;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Connection;

use function method_exists;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Service\Database\DatabaseSchemaService
 */
class DatabaseSchemaServiceTest extends UnitTestCase
{
    /**
     * @covers ::injectCache
     * @covers ::getColumnNames
     * @covers ::getTableNames
     */
    public function testInjectCachePopulatesColumnsAndTables(): void
    {
        $localDatbase = $this->createMock(Connection::class);
        $localDatbase->expects($this->never())->method('getSchemaManager');
        if (method_exists(Connection::class, 'createSchemaManager')) {
            $localDatbase->expects($this->never())->method('createSchemaManager');
        }

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
