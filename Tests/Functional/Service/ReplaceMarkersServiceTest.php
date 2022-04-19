<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Functional\Service;

use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Service\ReplaceMarkersService;
use In2code\In2publishCore\Domain\Service\TcaProcessingService;
use In2code\In2publishCore\Tests\FunctionalTestCase;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;

class ReplaceMarkersServiceTest extends FunctionalTestCase
{
    /**
     * @covers ::replaceSiteMarker
     */
    public function testReplaceSiteMarker()
    {
        $record = $this->getRecordStub('tx_unit_test_table');

        $flexFormTools = $this->createMock(FlexFormTools::class);
        $tcaProcessingService = $this->createMock(TcaProcessingService::class);

        $siteFinder = $this->createMock(SiteFinder::class);
        $siteFinder->method('getSiteByPageId')->willReturn(
            new Site('test', 1, [
                'rootPageId' => 1,
                'nested' => [
                    'custom' => 'test'
                ],
                'stringArray' => [
                    'string1',
                    'string2',
                    'string3'
                ],
                'boolOption' => false
            ])
        );

        $replaceMarkerService = new ReplaceMarkersService($flexFormTools, $tcaProcessingService, $siteFinder);

        $replacement = $replaceMarkerService->replaceMarkers(
            $record,
            '###SITE:rootPageId### ###SITE:nested.custom### ###SITE:stringArray### ###SITE:boolOption###',
            'tx_unit_test_field'
        );

        $this->assertSame('1 \'test\' \'string1\',\'string2\',\'string3\' 0', $replacement);
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
            'debug' => [
                'disableParentRecords' => $isParentRecordDisabled,
            ],
        ];
        $this->initializeIn2publishConfig($config);

        $record = $this->createMock(Record::class);
        $record->method('getTablename')->willReturn($table);
        return $record;
    }
}
