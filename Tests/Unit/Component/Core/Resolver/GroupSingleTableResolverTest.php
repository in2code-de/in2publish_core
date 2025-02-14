<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Resolver;

use In2code\In2publishCore\Component\Core\Demand\DemandsCollection;
use In2code\In2publishCore\Component\Core\Demand\Type\SelectDemand;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Resolver\GroupSingleTableResolver;
use In2code\In2publishCore\Tests\UnitTestCase;
use ReflectionProperty;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Resolver\GroupSingleTableResolver
 */
class GroupSingleTableResolverTest extends UnitTestCase
{
    /**
     * @covers ::getTargetTables
     */
    public function testGetTargetTables(): void
    {
        $resolver = new GroupSingleTableResolver();
        $resolver->configure('column_foo', 'foreign_table');

        $this->assertEquals(['foreign_table'], $resolver->getTargetTables());
    }

    /**
     * @covers ::configure
     */
    public function testConfigure(): void
    {
        $resolver = new GroupSingleTableResolver();
        $column = new ReflectionProperty(GroupSingleTableResolver::class, 'column');
        $column->setAccessible(true);
        $foreignTable = new ReflectionProperty(GroupSingleTableResolver::class, 'foreignTable');
        $foreignTable->setAccessible(true);

        $resolver->configure('column_foo', 'foreign_table');

        $this->assertEquals('column_foo', $column->getValue($resolver));
        $this->assertEquals('foreign_table', $foreignTable->getValue($resolver));
    }

    /**
     * @covers ::resolve
     */
    public function testResolveWithSimpleNumericValues(): void
    {
        $resolver = new GroupSingleTableResolver();
        $resolver->configure('group_field', 'tt_content');

        $demands = new DemandsCollection();
        // Covers Bugfix https://projekte.in2code.de/issues/69665
        // group_field may contain a comma separated list of values or integers
        $record = new DatabaseRecord(
            'some_table',
            1,
            ['group_field' => '1,2,3'],
            ['group_field' => 4],
            []
        );

        $resolver->resolve($demands, $record);

        $selectDemands = $demands->getDemandsByType(SelectDemand::class);
        $this->assertCount(1, $selectDemands['tt_content']);

        $expectedValues = [1,2,3,4];
        foreach ($selectDemands['tt_content'] as $selectDemand) {
            $value = array_keys($selectDemand['uid'])[0];
            $this->assertContains($value, $expectedValues);
        }
    }

    /**
     * @covers ::resolve
     */
    public function testResolveWithTablePrefixedValues(): void
    {
        $resolver = new GroupSingleTableResolver();
        $resolver->configure('group_field', 'tt_content');

        $demands = new DemandsCollection();
        $record = new DatabaseRecord(
            'some_table',
            1,
            ['group_field' => 'tt_content_1,tt_content_2,pages_3'],
            ['group_field' => 'tt_content_4'],
            []
        );

        $resolver->resolve($demands, $record);

        $selectDemands = $demands->getDemandsByType(SelectDemand::class);
        $this->assertCount(1, $selectDemands['tt_content']);

        $expectedValues = [1,2,4];
        foreach ($selectDemands['tt_content'] as $selectDemand) {
            $value = array_keys($selectDemand['uid'])[0];
            $this->assertContains($value, $expectedValues);
        }
    }

    /**
     * @covers ::resolve
     */
    public function testResolveWithEmptyValues(): void
    {
        $resolver = new GroupSingleTableResolver();
        $resolver->configure('group_field', 'tt_content');

        $demands = new DemandsCollection();
        $record = new DatabaseRecord(
            'some_table',
            1,
            ['group_field' => ''],
            ['group_field' => ''],
            []
        );

        $resolver->resolve($demands, $record);

        $selectDemands = $demands->getDemandsByType(SelectDemand::class);
        $this->assertEmpty($selectDemands);
    }

    /**
     * @covers ::resolve
     */
    public function testResolveWithMixedValues(): void
    {
        $resolver = new GroupSingleTableResolver();
        $resolver->configure('group_field', 'tt_content');

        $demands = new DemandsCollection();
        $record = new DatabaseRecord(
            'some_table',
            1,
            ['group_field' => '1,tt_content_2,pages_3'],
            ['group_field' => 'tt_content_4,5,other_table_6'],
            []
        );

        $resolver->resolve($demands, $record);

        $selectDemands = $demands->getDemandsByType(SelectDemand::class);
        $this->assertCount(1, $selectDemands['tt_content']);

        $expectedValues = [1,2,4,5];
        foreach ($selectDemands['tt_content'] as $selectDemand) {
            $value = array_keys($selectDemand['uid'])[0];
            $this->assertContains($value, $expectedValues);
        }
    }
}