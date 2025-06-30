<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Resolver;

use In2code\In2publishCore\Component\Core\Demand\DemandsCollection;
use In2code\In2publishCore\Component\Core\Demand\Type\SelectDemand;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Resolver\SelectResolver;
use In2code\In2publishCore\Service\ReplaceMarkersService;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use ReflectionProperty;

#[CoversMethod(SelectResolver::class, 'getTargetTables')]
#[CoversMethod(SelectResolver::class, 'configure')]
#[CoversMethod(SelectResolver::class, 'resolve')]
class SelectResolverTest extends UnitTestCase
{
    public function testGetTargetTables(): void
    {
        $selectResolver = new SelectResolver();
        $foreignTable = new ReflectionProperty(SelectResolver::class, 'foreignTable');
        $foreignTable->setAccessible(true);

        $selectResolver->configure(
            'column_foo',
            'foreignTable_foo',
            'foreignTableWhere_foo',
        );

        $this->assertEquals(['foreignTable_foo'], $selectResolver->getTargetTables());
    }

    public function testConfigure(): void
    {
        $selectResolver = new SelectResolver();
        $column = new ReflectionProperty(SelectResolver::class, 'column');
        $column->setAccessible(true);
        $foreignTable = new ReflectionProperty(SelectResolver::class, 'foreignTable');
        $foreignTable->setAccessible(true);
        $foreignTableWhere = new ReflectionProperty(SelectResolver::class, 'foreignTableWhere');
        $foreignTableWhere->setAccessible(true);

        $selectResolver->configure(
            'column_foo',
            'foreignTable_foo',
            'foreignTableWhere_foo',
        );

        $this->assertEquals('column_foo', $column->getValue($selectResolver));
        $this->assertEquals('foreignTable_foo', $foreignTable->getValue($selectResolver));
        $this->assertEquals('foreignTableWhere_foo', $foreignTableWhere->getValue($selectResolver));
    }

    public function testResolve(): void
    {
        $selectResolver = new SelectResolver();

        $replaceMarkersService = $this->createMock(ReplaceMarkersService::class);
        $selectResolver->injectReplaceMarkersService($replaceMarkersService);

        $selectResolver->configure(
            'column_foo',
            'foreignTable_foo',
            'foreignTableWhere_foo',
        );
        $demands = new DemandsCollection();
        $record = new DatabaseRecord('foreignTable_foo', 42, ['column_foo' => 'value_foo'], [], []);
        $selectResolver->resolve($demands, $record);

        $selectDemandForTableFoo = $demands->getDemandsByType(SelectDemand::class)['foreignTable_foo'];

        foreach ($selectDemandForTableFoo as $selectDemand) {
            $resolvedRecordInSelectDemand = $selectDemand['uid']['value_foo']['foreignTable_foo\42'];
            $this->assertEquals($resolvedRecordInSelectDemand, $record);
        }

        $this->assertEquals($record, $resolvedRecordInSelectDemand);
    }
}
