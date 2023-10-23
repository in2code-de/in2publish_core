<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Record\Model;

use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Dependency;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Tests\UnitTestCase;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Record\Model\Dependency
 */
class DependencyTest extends UnitTestCase
{
    /**
     * @covers ::__construct
     */
    public function testDependencyCanBeInstantiated(): void
    {
        $this->assertInstanceOf(
            Dependency::class,
            new Dependency(
                $this->createMock(Record::class),
                'classification',
                ['property' => 'value'],
                'requirement',
                'label',
                function () {
                    return ['arguments'];
                },
            ),
        );
    }

    /**
     * @covers ::addSupersedingDependency
     */
    public function testSupersedingDependencyCanBeAdded(): void
    {
        $dependency = new Dependency(
            $this->createMock(Record::class),
            'classification',
            ['property' => 'value'],
            'requirement',
            'label',
            function () {
                return ['arguments'];
            },
        );
        $supersedingDependency = new Dependency(
            $this->createMock(Record::class),
            'classification',
            ['property' => 'value'],
            'requirement',
            'label',
            function () {
                return ['arguments'];
            },
        );
        $dependency->addSupersedingDependency($supersedingDependency);
        $reflectionProperty = new \ReflectionProperty(Dependency::class, 'supersededBy');
        $reflectionProperty->setAccessible(true);
        $this->assertSame(
            [$supersedingDependency],
            $reflectionProperty->getValue($dependency),
        );
    }

    /**
     * @covers ::getPropertiesAsUidOrString
     */
    public function testGetPropertiesAsUidOrString(): void
    {
        $dependency = new Dependency(
            $this->createMock(Record::class),
            'classification',
            ['property_foo' => 'value_foo'],
            'requirement',
            'label',
            function () {
                return ['arguments'];
            },
        );
        $this->assertSame(
            'property_foo=value_foo',
            $dependency->getPropertiesAsUidOrString(),
        );

        $dependency2 = new Dependency(
            $this->createMock(Record::class),
            'classification',
            ['uid' => 4711],
            'requirement',
            'label',
            function () {
                return ['arguments'];
            },
        );

        $this->assertSame(
            '4711',
            $dependency2->getPropertiesAsUidOrString(),
        );
    }

    /**
     * @covers ::fulfill
     * @covers ::recordMatchesRequirements
     */
    public function testFulfillReturnsFalseIfRecordDoesNotExist()
    {
        // arrange
        $dependency = new Dependency(
            $this->createMock(Record::class),
            'classification',
            ['property' => 'value'],
            'requirement',
            'label',
            function () {
                return ['arguments'];
            },
        );
        $recordCollection = $this->createMock(RecordCollection::class);
        $recordCollection->expects($this->once())->method('getRecordsByProperties')->willReturn([]);

        // act
        $dependency->fulfill($recordCollection);

        // assert
        $this->assertFalse($dependency->isFulfilled());

        $reflectionProperty = new \ReflectionProperty(Dependency::class, 'reasons');
        $reflectionProperty->setAccessible(true);
        $reasons = $reflectionProperty->getValue($dependency);

        $this->assertFalse($reasons->isEmpty());

        $expectedReasonLabel = "LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:record.reason.missing_dependency";
        $actualLabel = $reasons->getAll()[0]->getLabel();

        $this->assertSame($expectedReasonLabel, $actualLabel);
    }

    /**
     * @covers ::fulfill
     * @covers ::recordMatchesRequirements
     */
    public function testFulfillReturnsTrueIfRecordIsUnchangedAndRequirementIsFullyPublished()
    {
        // arrange
        $dependency = new Dependency(
            $this->createMock(Record::class),
            'classification',
            ['property' => 'value'],
            'fully_published',
            'label',
            function () {
                return ['arguments'];
            },
        );

        $record = $this->createMock(DatabaseRecord::class);
        $record->expects($this->once())->method('getState')->willReturn(Record::S_UNCHANGED);
        $recordCollection = $this->createMock(RecordCollection::class);
        $recordCollection->expects($this->once())->method('getRecordsByProperties')->willReturn([$record]);

        // act
        $dependency->fulfill($recordCollection);

        // assert
        $this->assertTrue($dependency->isFulfilled());

        $reflectionProperty = new \ReflectionProperty(Dependency::class, 'reasons');
        $reflectionProperty->setAccessible(true);
        $reasons = $reflectionProperty->getValue($dependency);

        $this->assertTrue($reasons->isEmpty());
    }

    /**
     * @covers ::fulfill
     * @covers ::recordMatchesRequirements
     */
    public function testFulfillReturnsFalse()
    {
        // arrange
        $dependency = new Dependency(
            $this->createMock(Record::class),
            'classification',
            ['property' => 'value'],
            'enablecolumns',
            'Label for my dependency',
            function () {
                return ['arguments'];
            },
        );

        $GLOBALS['TCA']['classification']['ctrl']['enablecolumns'] = ['disabled', 'other_disabled_field'];

        $record = $this->createMock(DatabaseRecord::class);
        $record->expects($this->once())->method('getState')->willReturn(Record::S_MOVED);
        $record->expects($this->once())->method('getLocalProps')->willReturn(['disabled' => 0]);
        $record->expects($this->once())->method('getForeignProps')->willReturn(['disabled' => 1]);
        $recordCollection = $this->createMock(RecordCollection::class);
        $recordCollection->expects($this->once())->method('getRecordsByProperties')->willReturn([$record]);

        // act
        $dependency->fulfill($recordCollection);

        // assert
        $this->assertFalse($dependency->isFulfilled());

        $reflectionProperty = new \ReflectionProperty(Dependency::class, 'reasons');
        $reflectionProperty->setAccessible(true);
        $reasons = $reflectionProperty->getValue($dependency);
        $expectedReasonLabel = 'Label for my dependency';
        $actualLabel = $reasons->getAll()[0]->getLabel();

        $this->assertSame($expectedReasonLabel, $actualLabel);
    }
    /**
     * @covers ::areSupersededDependenciesFulfilled
     * @covers ::isSupersededByUnfulfilledDependency
     */
    public function testAreSupersededDependenciesFulfilled()
    {
        // arrange
        $dependencyWithFulfilledDependencies = new Dependency(
            $this->createMock(Record::class),
            'classification',
            ['property' => 'value'],
            'requirement',
            'label',
            function () {
                return ['arguments'];
            },
        );
        $dependencyWithUnfulfilledDependencies = new Dependency(
            $this->createMock(Record::class),
            'classification',
            ['property' => 'value'],
            'requirement',
            'label',
            function () {
                return ['arguments'];
            },
        );
        $dependency1 = $this->createMock(Dependency::class);
        $dependency2 = $this->createMock(Dependency::class);
        $dependency3 = $this->createMock(Dependency::class);
        $dependency1->method('isFulfilled')->willReturn(true);
        $dependency2->method('isFulfilled')->willReturn(true);
        $dependency3->method('isFulfilled')->willReturn(false);

        $fulfilledSupersedingDependencies = new \ReflectionProperty($dependencyWithFulfilledDependencies, 'supersededBy');
        $fulfilledSupersedingDependencies->setAccessible(true);
        $fulfilledSupersedingDependencies->setValue($dependencyWithFulfilledDependencies, [$dependency1, $dependency2]);

        $unfulfilledSupersedingDependencies = new \ReflectionProperty($dependencyWithUnfulfilledDependencies, 'supersededBy');
        $unfulfilledSupersedingDependencies->setAccessible(true);
        $unfulfilledSupersedingDependencies->setValue($dependencyWithUnfulfilledDependencies, [$dependency2, $dependency3]);

        $testMethodUnfulfilled = new \ReflectionMethod($dependencyWithUnfulfilledDependencies, 'areSupersededDependenciesFulfilled');
        $testMethodFulfilled = new \ReflectionMethod($dependencyWithFulfilledDependencies, 'areSupersededDependenciesFulfilled');

        // act
        $testMethodUnfulfilled->setAccessible(true);
        $actualResultUnfilled = $testMethodUnfulfilled->invoke($dependencyWithUnfulfilledDependencies);

        $testMethodFulfilled->setAccessible(true);
        $actualResultFulfilled = $testMethodFulfilled->invoke($dependencyWithFulfilledDependencies);


        // assert
        $this->assertFalse($actualResultUnfilled);
        $this->assertTrue($actualResultFulfilled);

        $this->assertFalse($dependencyWithFulfilledDependencies->isSupersededByUnfulfilledDependency());
        $this->assertTrue($dependencyWithUnfulfilledDependencies->isSupersededByUnfulfilledDependency());
    }
}
