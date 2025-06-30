<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Service;

/*
 * Copyright notice
 *
 * (c) 2020 in2code.de and the following authors:
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

use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Service\ReplaceMarkersService;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * @coversDefaultClass \In2code\In2publishCore\Service\ReplaceMarkersService
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
#[CoversMethod(ReplaceMarkersService::class, 'replaceMarkers')]
#[CoversMethod(ReplaceMarkersService::class, 'replacePageTsConfigMarkers')]
#[CoversMethod(ReplaceMarkersService::class, 'replaceSiteMarker')]
#[CoversMethod(ReplaceMarkersService::class, 'quoteParsedSiteConfiguration')]
#[CoversMethod(ReplaceMarkersService::class, 'replaceRecFieldMarker')]
class ReplaceMarkersServiceTest extends UnitTestCase
{
    public function testReplaceMarkerServiceSupportsPageTsConfigId(): void
    {
        $record = $this->getRecordStub('tx_unit_test_table');

        $flexFormTools = $this->createMock(FlexFormTools::class);
        $siteFinder = $this->createMock(SiteFinder::class);
        $connection = $this->createMock(Connection::class);

        $replaceMarkerService = new class extends ReplaceMarkersService {
            protected function getPagesTsConfig(int $pageIdentifier): array
            {
                return [
                    'TCEFORM.' => [
                        'tx_unit_test_table.' => [
                            'tx_unit_test_field.' => [
                                'PAGE_TSCONFIG_ID' => 52,
                            ],
                        ],
                    ],
                ];
            }
        };
        $replaceMarkerService->injectLocalDatabase($connection);
        $replaceMarkerService->injectFlexFormTools($flexFormTools);
        $replaceMarkerService->injectSiteFinder($siteFinder);

        $replacement = $replaceMarkerService->replaceMarkers(
            $record,
            'foo ###PAGE_TSCONFIG_ID### bar',
            'tx_unit_test_field',
        );

        self::assertSame('foo 52 bar', $replacement);
    }

    public function testReplaceMarkerServiceSupportsPageTsConfigIdList(): void
    {
        $record = $this->getRecordStub('tx_unit_test_table');

        $flexFormTools = $this->createMock(FlexFormTools::class);
        $siteFinder = $this->createMock(SiteFinder::class);
        $connection = $this->createMock(Connection::class);

        $replaceMarkerService = new class extends ReplaceMarkersService {
            protected function getPagesTsConfig(int $pageIdentifier): array
            {
                return [
                    'TCEFORM.' => [
                        'tx_unit_test_table.' => [
                            'tx_unit_test_field.' => [
                                'PAGE_TSCONFIG_IDLIST' => '52, 11a, 9a',
                            ],
                        ],
                    ],
                ];
            }
        };
        $replaceMarkerService->injectLocalDatabase($connection);
        $replaceMarkerService->injectFlexFormTools($flexFormTools);
        $replaceMarkerService->injectSiteFinder($siteFinder);

        $replacement = $replaceMarkerService->replaceMarkers(
            $record,
            'foo ###PAGE_TSCONFIG_IDLIST### bar',
            'tx_unit_test_field',
        );

        self::assertSame('foo 52,11,9 bar', $replacement);
    }

    public function testReplaceSiteMarker(): void
    {
        $record = $this->getRecordStub('tx_unit_test_table');

        $flexFormTools = $this->createMock(FlexFormTools::class);
        $siteFinder = $this->createMock(SiteFinder::class);
        $siteFinder->method('getSiteByPageId')->willReturn(
            new Site('test', 1, [
                'rootPageId' => 1,
                'nested' => [
                    'custom' => 'test',
                ],
                'stringArray' => [
                    'string1',
                    'string2',
                    'string3' => [
                        'foo1',
                        'foo2',
                    ],
                ],
                'boolOption' => false,
            ]),
        );
        $connection = $this->createMock(Connection::class);
        $connection->method('quote')->willReturnCallback(static fn(string $input): string => "'$input'");

        $replaceMarkerService = new ReplaceMarkersService();
        $replaceMarkerService->injectLocalDatabase($connection);
        $replaceMarkerService->injectFlexFormTools($flexFormTools);
        $replaceMarkerService->injectSiteFinder($siteFinder);

        $replacement = $replaceMarkerService->replaceMarkers(
            $record,
            '###SITE:rootPageId### ###SITE:nested.custom### ###SITE:stringArray### ###SITE:boolOption###',
            'tx_unit_test_field',
        );

        self::assertSame('1 \'test\' \'string1\',\'string2\',\'foo1\',\'foo2\' 0', $replacement);
    }

    public function testReplaceRecFieldMarkerPrefersLocalValue(): void
    {
        $record = new DatabaseRecord(
            'foo',
            123,
            ['uid' => 123, 'pid' => 0, 'tx_unit_test_field' => 'bar'],
            ['uid' => 123, 'pid' => 0, 'tx_unit_test_field' => 'fun'],
            [],
        );

        $localDatabase = $this->createMock(Connection::class);
        $localDatabase->method('quote')->willReturnCallback(static fn(string $input): string => '"' . $input . '"');

        $replaceMarkerService = new ReplaceMarkersService();
        $replaceMarkerService->injectLocalDatabase($localDatabase);

        $replacement = $replaceMarkerService->replaceMarkers(
            $record,
            'AAAA ###REC_FIELD_tx_unit_test_field### BBBB',
            '_unused',
        );

        self::assertSame('AAAA "bar" BBBB', $replacement);
    }

    public function testReplaceRecFieldMarkerUsesForeignAsFallback(): void
    {
        $record = new DatabaseRecord(
            'foo',
            123,
            ['uid' => 123, 'pid' => 0],
            ['uid' => 123, 'pid' => 0, 'tx_unit_test_field' => 'fun'],
            [],
        );

        $localDatabase = $this->createMock(Connection::class);
        $localDatabase->method('quote')->willReturnCallback(static fn(string $input): string => '"' . $input . '"');

        $replaceMarkerService = new ReplaceMarkersService();
        $replaceMarkerService->injectLocalDatabase($localDatabase);

        $replacement = $replaceMarkerService->replaceMarkers(
            $record,
            'AAAA ###REC_FIELD_tx_unit_test_field### BBBB',
            '_unused',
        );

        self::assertSame('AAAA "fun" BBBB', $replacement);
    }

    /**
     * ###STORAGE_PID### is not tested as it uses static methods which can not be mocked and requires caches
     */
    public function testReplacePageMarker(): void
    {
        $record = new DatabaseRecord(
            'foo',
            123,
            ['uid' => 123, 'pid' => 0],
            ['uid' => 123, 'pid' => 0, 'tx_unit_test_field' => 'fun'],
            [],
        );

        $localDatabase = $this->createMock(Connection::class);
        $localDatabase->method('quote')->willReturnCallback(static fn(string $input): string => '"' . $input . '"');

        $replaceMarkerService = new ReplaceMarkersService();
        $replaceMarkerService->injectLocalDatabase($localDatabase);

        $replacement = $replaceMarkerService->replaceMarkers(
            $record,
            'AAA ###CURRENT_PID### BBB ###THIS_UID### CCC',
            '_unused',
        );

        self::assertSame('AAA 0 BBB 123 CCC', $replacement);
    }

    public function testReplacePageMarkerStoragePid(): void
    {
        self::markTestSkipped(
            '###STORAGE_PID### is not tested as it uses static methods which can not be mocked and requires caches',
        );

        $record = new DatabaseRecord(
            'foo',
            123,
            ['uid' => 123, 'pid' => 0],
            ['uid' => 123, 'pid' => 0, 'tx_unit_test_field' => 'fun'],
            [],
        );

        $localDatabase = $this->createMock(Connection::class);
        $localDatabase->method('quote')->willReturnCallback(static fn(string $input): string => '"' . $input . '"');

        $replaceMarkerService = new ReplaceMarkersService();
        $replaceMarkerService->injectLocalDatabase($localDatabase);

        $replacement = $replaceMarkerService->replaceMarkers(
            $record,
            'AAA ###STORAGE_PID### CCC',
            '_unused',
        );

        $this->assertSame('AAA ###STORAGE_PID### CCC', $replacement);
    }

    public function testReplaceFlexFormFieldMarkers(): void
    {
        self::markTestSkipped('FlexFormFieldMarkers rely on static methods from TYPO3 which can not be mocked.');
    }

    /**
     * @param string $table
     * @param array $getIgnoreFields
     * @param bool $isParentRecordDisabled
     *
     * @return Record
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    protected function getRecordStub(string $table, array $getIgnoreFields = [], bool $isParentRecordDisabled = false)
    {
        $config = [
            'ignoreFieldsForDifferenceView' => [
                $table => $getIgnoreFields,
            ],
        ];
        $this->initializeIn2publishConfig($config);

        $record = $this->createMock(DatabaseRecord::class);
        $record->method('getClassification')->willReturn($table);
        return $record;
    }
}
