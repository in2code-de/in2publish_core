<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\PreProcessing\Service;

use In2code\In2publishCore\Component\Core\PreProcessing\Service\TcaEscapingMarkerService;
use In2code\In2publishCore\Tests\UnitTestCase;
use TYPO3\CMS\Core\Database\Connection;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\PreProcessing\Service\TcaEscapingMarkerService
 */
class TcaEscapingMarkerServiceTest extends UnitTestCase
{
    /**
     * @covers ::escapeMarkedIdentifier
     */
    public function testEscapeMarkedIdentifierReplacesAllMarkersWithQuotedIdentifiers(): void
    {
        $sql = 'SELECT * FROM {#table} WHERE {#field} = {#value}';
        $expected = 'SELECT * FROM `table` WHERE `field` = `value`';
        $database = $this->createMock(Connection::class);
        $database->expects($this->exactly(3))
                 ->method('quoteIdentifier')
                 ->withConsecutive(
                     ['table'],
                     ['field'],
                     ['value'],
                 )
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

    /**
     * @covers ::escapeMarkedIdentifier
     */
    public function testEscapeMarkedIdentifierOnlyReplacesValidMarkers(): void
    {
        $sql = "SELECT * FROM '#table' WHERE {#field} = {#value}";
        $expected = "SELECT * FROM '#table' WHERE 'field' = 'value'";
        $database = $this->createMock(Connection::class);
        $database->expects($this->exactly(2))
                 ->method('quoteIdentifier')
                 ->withConsecutive(
                     ['field'],
                     ['value'],
                 )
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
