<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\PreProcessing\Service;

use In2code\In2publishCore\Component\Core\PreProcessing\Service\TcaEscapingMarkerService;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use TYPO3\CMS\Core\Database\Connection;

#[CoversMethod(TcaEscapingMarkerService::class, 'escapeMarkedIdentifier')]
class TcaEscapingMarkerServiceTest extends UnitTestCase
{
    public function testEscapeMarkedIdentifierReplacesAllMarkersWithQuotedIdentifiers(): void
    {
        $sql = 'SELECT * FROM {#table} WHERE {#field} = {#value}';
        $expected = 'SELECT * FROM `table` WHERE `field` = `value`';
        $database = $this->createMock(Connection::class);
        $database->expects($this->exactly(3))
                 ->method('quoteIdentifier')
                 ->willReturnOnConsecutiveCalls(
                     '`table`',
                     '`field`',
                     '`value`',
                 );
        $tcaEscapingMarkerService = new TcaEscapingMarkerService();
        $tcaEscapingMarkerService->injectLocalDatabase($database);
        $result = $tcaEscapingMarkerService->escapeMarkedIdentifier($sql);
        $this->assertSame($expected, $result);
    }

    public function testEscapeMarkedIdentifierOnlyReplacesValidMarkers(): void
    {
        $sql = "SELECT * FROM '#table' WHERE {#field} = {#value}";
        $expected = "SELECT * FROM '#table' WHERE 'field' = 'value'";
        $database = $this->createMock(Connection::class);
        $database->expects($this->exactly(2))
                 ->method('quoteIdentifier')
                 ->willReturnOnConsecutiveCalls(
                     '\'field\'',
                     '\'value\'',
                 );
        $tcaEscapingMarkerService = new TcaEscapingMarkerService();
        $tcaEscapingMarkerService->injectLocalDatabase($database);
        $result = $tcaEscapingMarkerService->escapeMarkedIdentifier($sql);
        $this->assertSame($expected, $result);
    }
}
