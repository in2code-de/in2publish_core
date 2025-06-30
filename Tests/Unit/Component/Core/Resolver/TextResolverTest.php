<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Resolver;

use In2code\In2publishCore\Component\Core\Demand\DemandsCollection;
use In2code\In2publishCore\Component\Core\Demand\Type\SelectDemand;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Resolver\TextResolver;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

#[CoversMethod(TextResolver::class, 'findRelationsInText')]
#[CoversMethod(TextResolver::class, 'getTargetTables')]
#[CoversMethod(TextResolver::class, 'resolve')]
class TextResolverTest extends UnitTestCase
{
    public function testTargetTables(): void
    {
        $textResolver = new TextResolver();
        $expectedTargetTables = ['sys_file'];
        $this->assertSame($expectedTargetTables, $textResolver->getTargetTables());
    }

    public function testResolverFindsFileRelations(): void
    {
        $textResolver = new TextResolver();
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $textResolver->injectEventDispatcher($eventDispatcher);
        $textResolver->configure('header_link');
        $demands = new DemandsCollection();
        $databaseRecord = new DatabaseRecord(
            'tt_content',
            1,
            ['header_link' => 't3://page?uid=1 t3://page?uid=2 t3://file?uid=3'],
            [],
            [],
        );
        $textResolver->resolve($demands, $databaseRecord);

        $selectDemand = $demands->getDemandsByType(SelectDemand::class);

        $this->assertArrayHasKey('sys_file', $selectDemand);
        $fileArray = $selectDemand['sys_file'];

        foreach ($fileArray as $subArray) {
            $this->assertArrayHasKey('uid', $subArray);
            $this->assertArrayHasKey('tt_content\1', $subArray['uid'][3]);
        }
    }
}
