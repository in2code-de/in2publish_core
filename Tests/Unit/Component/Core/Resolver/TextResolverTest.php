<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Resolver;

use In2code\In2publishCore\Component\Core\Demand\DemandsCollection;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Resolver\TextResolver;
use In2code\In2publishCore\Tests\UnitTestCase;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Resolver\TextResolver
 */
class TextResolverTest extends UnitTestCase
{
    /**
     * @covers ::getTargetTables
     */
    public function testTargetTables(): void
    {
        $textResolver = new TextResolver();
        $expectedTargetTables = ['sys_file', 'pages'];
        $this->assertSame($expectedTargetTables, $textResolver->getTargetTables());
    }

    /**
     * @covers ::resolve
     */
    public function testResolverFindsPageRelations(): void
    {
        $textResolver = new TextResolver();
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $textResolver->injectEventDispatcher($eventDispatcher);
        $textResolver->configure('header_link');
        $demands = new DemandsCollection();
        $databaseRecord = new DatabaseRecord(
            'tt_content',
            1,
            ['header_link' => 't3://page?uid=1 t3://page?uid=2'],
            [],
            []
        );
        $textResolver->resolve($demands, $databaseRecord);

        $selectDemand = $demands->getSelect();

//       $expectedDemandStructure = ['pages'][['uid' => [1 => 'tt_content\1', 2 => 'tt_content\1']]];

        $this->assertArrayHasKey('pages', $selectDemand);
        $pagesArray = $selectDemand['pages'];
        foreach ($pagesArray as $subArray) {
            $this->assertArrayHasKey('uid', $subArray);
            $this->assertArrayHasKey('tt_content\1', $subArray['uid'][1]);
            $this->assertArrayHasKey('tt_content\1', $subArray['uid'][2]);
        }
    }
}
