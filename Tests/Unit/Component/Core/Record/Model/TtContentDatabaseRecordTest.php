<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Record\Model;

use In2code\In2publishCore\Component\Core\Record\Model\Dependency;
use In2code\In2publishCore\Component\Core\Record\Model\TtContentDatabaseRecord;
use In2code\In2publishCore\Tests\UnitTestCase;
use ReflectionProperty;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Record\Model\TtContentDatabaseRecord
 */
class TtContentDatabaseRecordTest extends UnitTestCase
{
    /**
     * @covers ::__construct
     * @covers ::getId
     * @covers ::getClassification
     * @covers ::getLocalProps
     * @covers ::getForeignProps
     * @covers ::getDependencies
     */
    public function testConstructor(): void
    {
        $ttContentDatabaseRecord = new TtContentDatabaseRecord(
            'table_foo',
            42,
            ['prop1' => 'value1'],
            ['prop2' => 'value2'],
            ['prop3' => 'value3'],
        );
        $this->assertInstanceOf(TtContentDatabaseRecord::class, $ttContentDatabaseRecord);
        $this->assertSame('table_foo', $ttContentDatabaseRecord->getClassification());
        $this->assertSame(42, $ttContentDatabaseRecord->getId());
        $this->assertSame(['prop1' => 'value1'], $ttContentDatabaseRecord->getLocalProps());
        $this->assertSame(['prop2' => 'value2'], $ttContentDatabaseRecord->getForeignProps());
        $this->assertSame([], $ttContentDatabaseRecord->getDependencies());

        $reflectionProperty = new ReflectionProperty(TtContentDatabaseRecord::class, 'ignoredProps');
        $reflectionProperty->setAccessible(true);
        $this->assertSame(['prop3' => 'value3'], $reflectionProperty->getValue($ttContentDatabaseRecord));
    }

    /**
     * @covers ::calculateDependencies
     * @covers ::calculateShortcutDependencies
     */
    public function testCalculateDependenciesCorrectlyResolvesDependencies(): void
    {
        $ttContentDatabaseRecord = new TtContentDatabaseRecord(
            'table_foo',
            42,
            ['CType' => 'shortcut', 'records' => 'table_bar_1, table_bar_2'],
            [],
            [],
        );

        $dependency1 = $ttContentDatabaseRecord->calculateDependencies()[0];
        $this->assertInstanceOf(Dependency::class, $dependency1);
        $this->assertSame('table_bar', $dependency1->getClassification());
        $this->assertSame(['uid' => '1'], $dependency1->getProperties());
        $this->assertSame(Dependency::REQ_FULL_PUBLISHED, $dependency1->getRequirement());

        $dependency2 = $ttContentDatabaseRecord->calculateDependencies()[1];
        $this->assertInstanceOf(Dependency::class, $dependency2);
        $this->assertSame('table_bar', $dependency2->getClassification());
        $this->assertSame(['uid' => '2'], $dependency2->getProperties());
        $this->assertSame(Dependency::REQ_FULL_PUBLISHED, $dependency2->getRequirement());

        $recordWithinDependency = $dependency1->getRecord();
        $this->assertInstanceOf(TtContentDatabaseRecord::class, $recordWithinDependency);
        $this->assertSame('table_foo', $recordWithinDependency->getClassification());
        $this->assertSame(42, $recordWithinDependency->getId());
        $this->assertSame(
            ['CType' => 'shortcut', 'records' => 'table_bar_1, table_bar_2'],
            $recordWithinDependency->getLocalProps(),
        );

        $recordWithinDependencyLevel2 = $recordWithinDependency->getDependencies()[0];
        $this->assertSame('table_bar', $recordWithinDependencyLevel2->getClassification());
        $this->assertSame(['uid' => '1'], $recordWithinDependencyLevel2->getProperties());
    }

    /**
     * @covers ::calculateDependencies
     * @covers ::calculateShortcutDependencies
     */
    public function testCorrectNumberOfDependenciesIsCalculated(): void
    {
        $ttContentDatabaseRecord0 = new TtContentDatabaseRecord(
            'table_foo',
            42,
            [],
            [],
            [],
        );

        $ttContentDatabaseRecord1 = new TtContentDatabaseRecord(
            'table_foo',
            42,
            ['CType' => 'shortcut', 'records' => 'table_bar_1'],
            [],
            [],
        );

        $ttContentDatabaseRecord3 = new TtContentDatabaseRecord(
            'table_foo',
            42,
            ['CType' => 'shortcut', 'records' => 'table_bar_1'],
            ['CType' => 'shortcut', 'records' => 'table_bar_4,table_bar_5'],
            [],
        );

        $ttContentDatabaseRecord6 = new TtContentDatabaseRecord(
            'table_foo',
            42,
            ['CType' => 'shortcut', 'records' => 'table_bar_1,table_bar_2,table_bar_3'],
            ['CType' => 'shortcut', 'records' => 'table_bar_4,table_bar_5,table_bar_6'],
            [],
        );

        $this->assertSame(0, count($ttContentDatabaseRecord0->calculateDependencies()));
        $this->assertSame(1, count($ttContentDatabaseRecord1->calculateDependencies()));
        $this->assertSame(3, count($ttContentDatabaseRecord3->calculateDependencies()));
        $this->assertSame(6, count($ttContentDatabaseRecord6->calculateDependencies()));
    }

    /**
     * @covers ::calculateDependencies
     * @covers ::calculateShortcutDependencies
     */
    public function testNoDependencyIsFoundIfNoValidShortcutIsFound(): void
    {
        $ttContentDatabaseRecord1 = new TtContentDatabaseRecord(
            'table_foo',
            42,
            ['CType' => 'shortcut', 'records' => ''],
            [],
            [],
        );

        $ttContentDatabaseRecord2 = new TtContentDatabaseRecord(
            'table_foo',
            42,
            ['CType' => 'shortcut', 'records' => 'tableWithoutUid'],
            [],
            [],
        );

        $this->assertSame(0, count($ttContentDatabaseRecord1->calculateDependencies()));
        $this->assertSame(0, count($ttContentDatabaseRecord2->calculateDependencies()));
    }
}
