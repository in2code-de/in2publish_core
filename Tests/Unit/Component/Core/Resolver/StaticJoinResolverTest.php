<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Resolver;

use In2code\In2publishCore\Component\Core\Demand\DemandsCollection;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Resolver\StaticJoinResolver;
use In2code\In2publishCore\Tests\UnitTestCase;

class StaticJoinResolverTest extends UnitTestCase
{
    /**
     * @covers ::configure
     */
    public function testConfigure(): void
    {
        $staticJoinResolver = new StaticJoinResolver();
        $mmTable = new \ReflectionProperty(StaticJoinResolver::class, 'mmTable');
        $joinTable = new \ReflectionProperty(StaticJoinResolver::class, 'joinTable');
        $additionalWhere = new \ReflectionProperty(StaticJoinResolver::class, 'additionalWhere');
        $property = new \ReflectionProperty(StaticJoinResolver::class, 'property');
        $mmTable->setAccessible(true);
        $joinTable->setAccessible(true);
        $additionalWhere->setAccessible(true);
        $property->setAccessible(true);

        $staticJoinResolver->configure(
            'mmTable',
            'joinTable',
            'additionalWhere',
            'property'
        );
        $this->assertSame('mmTable', $mmTable->getValue($staticJoinResolver));
        $this->assertSame('joinTable', $joinTable->getValue($staticJoinResolver));
        $this->assertSame('additionalWhere', $additionalWhere->getValue($staticJoinResolver));
        $this->assertSame('property', $property->getValue($staticJoinResolver));
    }

    /**
     * @uses configure
     * @covers getTargetTables
     */
    public function testGetTargetTables(): void
    {
        $staticJoinResolver = new StaticJoinResolver();
        $staticJoinResolver->configure(
            'mmTable',
            'joinTable',
            'additionalWhere',
            'property'
        );

        $this->assertEquals(['joinTable'], $staticJoinResolver->getTargetTables());
    }

    /**
     * @uses configure
     * @covers ::resolve
     */
    public function testResolve(): void
    {
        $staticJoinResolver = new StaticJoinResolver();
        $staticJoinResolver->configure(
            'mmTable',
            'joinTable',
            'additionalWhere',
            'property'
        );

        $demands = new DemandsCollection();
        $record = new DatabaseRecord('table_foo', 42, ['local_prop1' => 'value_1'],[],[]);

        $staticJoinResolver->resolve($demands, $record);

        $joinDemands = $demands->getJoin();

        $resolvedRecordInJoinDemand = $joinDemands['mmTable']['joinTable']['additionalWhere']['property'][42]['table_foo\42'];

        $this->assertEquals($record, $resolvedRecordInJoinDemand);
    }
}
