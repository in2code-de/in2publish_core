<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Resolver;

use In2code\In2publishCore\Component\Core\Demand\DemandsCollection;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Resolver\SelectMmResolver;
use In2code\In2publishCore\Service\ReplaceMarkersService;
use In2code\In2publishCore\Tests\UnitTestCase;

/**
 * @coversDefaultClass  \In2code\In2publishCore\Component\Core\Resolver\SelectMmResolver
 */
class SelectMmResolverTest extends UnitTestCase
{
    /**
     * @uses ::configure
     * @covers ::getTargetTables
     */
    public function testGetTargetTables(): void
    {
        $selectMmResolver = $this->getConfiguredMmSelectResolver();
        $this->assertEquals(['foreignTable'], $selectMmResolver->getTargetTables());
    }

    /**
     * @covers ::configure
     */
    public function testConfigure()
    {
        $selectMmResolver = new SelectMmResolver();
        $foreignTableWhere = new \ReflectionProperty(SelectMmResolver::class, 'foreignTableWhere');
        $foreignTableWhere->setAccessible(true);
        $column = new \ReflectionProperty(SelectMmResolver::class, 'column');
        $column->setAccessible(true);
        $mmTable = new \ReflectionProperty(SelectMmResolver::class, 'mmTable');
        $mmTable->setAccessible(true);
        $foreignTable = new \ReflectionProperty(SelectMmResolver::class, 'foreignTable');
        $foreignTable->setAccessible(true);
        $selectField = new \ReflectionProperty(SelectMmResolver::class, 'selectField');
        $selectField->setAccessible(true);

        $selectMmResolver->configure(
            'foreignTableWhere',
            'column',
            'mmTable',
            'foreignTable',
            'selectField'
        );

        $this->assertEquals('foreignTableWhere', $foreignTableWhere->getValue($selectMmResolver));
        $this->assertEquals('column', $column->getValue($selectMmResolver));
        $this->assertEquals('mmTable', $mmTable->getValue($selectMmResolver));
        $this->assertEquals('foreignTable', $foreignTable->getValue($selectMmResolver));
        $this->assertEquals('selectField', $selectField->getValue($selectMmResolver));
    }

    /**
     * @covers ::resolve
     */
    public function testResolve()
    {
        $selectMmResolver = $this->getConfiguredMmSelectResolver();

        $replaceMarkersService = $this->createMock(ReplaceMarkersService::class);
        $replaceMarkersService->expects($this->once())->method('replaceMarkers')->willReturn('foreignTableWhereClause');
        $selectMmResolver->injectReplaceMarkersService($replaceMarkersService);

        $demands = new DemandsCollection();
        $record = new DatabaseRecord('table_foo', 42, ['local_prop1' => 'value_1'],[],[]);

        $selectMmResolver->resolve($demands, $record);

        $joinDemand = $demands->getJoin();
        $resolvedRecord = $joinDemand['mmTable']['foreignTable']['foreignTableWhereClause']['selectField'][42]['table_foo\42'];
        $this->assertEquals($resolvedRecord, $record);
    }

    /**
     * @covers ::resolve
     */
    public function testResolveWithAdditionalWhere()
    {
        $selectMmResolver = new SelectMmResolver();
        $selectMmResolver->configure(
            'AND {#foreignTable}.{#column} LIKE \'%value%\' ORDER BY uid',
            'column',
            'mmTable',
            'foreignTable',
            'selectField'
        );

        $record = new DatabaseRecord('table_foo', 42, ['value_foo' => 'value_1'],[],[]);

        $additionalWhere = 'AND {#foreignTable}.{#column} LIKE \'%value%\' ORDER BY uid';

        $replaceMarkersService = $this->createMock(ReplaceMarkersService::class);
        $replaceMarkersService->expects(
            $this->once())
            ->method('replaceMarkers')
            ->with($record,$additionalWhere,'column')
            ->willReturn('AND foreignTable.column LIKE \'%value%\'');

        $selectMmResolver->injectReplaceMarkersService($replaceMarkersService);

        $demands = new DemandsCollection();

        $selectMmResolver->resolve($demands, $record);

        $joinDemand = $demands->getJoin();
        $resolvedRecord = $joinDemand['mmTable']['foreignTable']['AND foreignTable.column LIKE \'%value%\'']['selectField'][42]['table_foo\42'];
        $this->assertEquals($resolvedRecord, $record);
    }

    protected function getConfiguredMmSelectResolver(): SelectMmResolver
    {
        $selectMmResolver = new SelectMmResolver();
        $selectMmResolver->configure(
            'foreignTableWhere',
            'column',
            'mmTable',
            'foreignTable',
            'selectField'
        );
        return $selectMmResolver;
    }

}
