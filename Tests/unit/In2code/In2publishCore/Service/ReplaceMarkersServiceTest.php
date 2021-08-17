<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\In2code\In2publishCore\Service;

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

use Codeception\Test\Unit;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Service\ReplaceMarkersService;
use In2code\In2publishCore\Tests\UnitTester;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass \In2code\In2publishCore\Domain\Service\ReplaceMarkersService
 */
class ReplaceMarkersServiceTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected $beUserBackup = null;

    protected function _before()
    {
        $this->tester->setUp();

        $this->beUserBackup = $GLOBALS['BE_USER'] ?? null;
    }

    protected function _after()
    {
        $this->tester->tearDown();

        $GLOBALS['BE_USER'] = $this->beUserBackup;
    }

    /**
     * @covers ::replaceMarkers
     * @covers ::replacePageTsConfigMarkers
     */
    public function testReplaceMarkerServiceSupportsPageTsConfigId()
    {
        $record = $this->getRecordStub('tx_unit_test_table');

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
        $replacement = $replaceMarkerService->replaceMarkers(
            $record,
            'foo ###PAGE_TSCONFIG_ID### bar',
            'tx_unit_test_field'
        );

        $this->assertSame('foo 52 bar', $replacement);
    }

    /**
     * @covers ::replaceMarkers
     * @covers ::replacePageTsConfigMarkers
     */
    public function testReplaceMarkerServiceSupportsPageTsConfigIdList()
    {
        $record = $this->getRecordStub('tx_unit_test_table');

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
        $replacement = $replaceMarkerService->replaceMarkers(
            $record,
            'foo ###PAGE_TSCONFIG_IDLIST### bar',
            'tx_unit_test_field'
        );

        $this->assertSame('foo 52,11,9 bar', $replacement);
    }

    /**
     * @param string $table
     * @param array $getIgnoreFields
     * @param bool $isParentRecordDisabled
     *
     * @return Record
     */
    protected function getRecordStub(string $table, array $getIgnoreFields = [], bool $isParentRecordDisabled = false)
    {
        /** @var Record|MockObject $stub */
        $stub = $this->getMockBuilder(Record::class)
                     ->setMethods(['getIgnoreFields', 'isParentDisabled'])
                     ->disableOriginalConstructor()
                     ->getMock();

        $stub->method('getIgnoreFields')->will($this->returnValue($getIgnoreFields));
        $stub->method('isParentDisabled')->will($this->returnValue($isParentRecordDisabled));

        $stub->setTableName($table);

        return $stub;
    }
}
