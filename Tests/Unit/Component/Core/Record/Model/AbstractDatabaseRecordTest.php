<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Record\Model;

use In2code\In2publishCore\Component\Core\Record\Model\AbstractDatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Dependency;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Tests\UnitTestCase;

use function iterator_to_array;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Record\Model\AbstractDatabaseRecord
 */
class AbstractDatabaseRecordTest extends UnitTestCase
{
    public function testCalculateDependenciesAddsDependencyForTranslation(): void
    {
        $localProps = $foreignProps = [
            'uid' => 123,
            'sys_lang' => 1,
            'sys_lang_parent' => 15,
            'enable_1' => true,
            'enable_2' => true,
        ];
        $testRecord = $this->createRecord('foo', 123, $localProps, $foreignProps, []);

        $dependencies = $testRecord->getDependencies();
        self::assertCount(2, $dependencies);
        $dependency = $dependencies[0];
        self::assertSame('foo', $dependency->getClassification());
        self::assertSame(['uid' => 15], $dependency->getProperties());
        self::assertSame(Dependency::REQ_EXISTING, $dependency->getRequirement());
        $dependency = $dependencies[1];
        self::assertSame('foo', $dependency->getClassification());
        self::assertSame(['uid' => 15], $dependency->getProperties());
        self::assertSame(Dependency::REQ_ENABLECOLUMNS, $dependency->getRequirement());
    }

    public function testCalculateDependenciesAddsDependenciesForParentRecord(): void
    {
        $localProps = $foreignProps = [
            'uid' => 123,
            'pid' => 7,
        ];
        $testRecord = $this->createRecord('foo', 123, $localProps, $foreignProps, []);

        $dependencies = $testRecord->getDependencies();
        self::assertCount(2, $dependencies);
        $dependency = $dependencies[0];
        self::assertSame('pages', $dependency->getClassification());
        self::assertSame(['uid' => 7], $dependency->getProperties());
        self::assertSame(Dependency::REQ_EXISTING, $dependency->getRequirement());
        $dependency = $dependencies[1];
        self::assertSame('pages', $dependency->getClassification());
        self::assertSame(['uid' => 7], $dependency->getProperties());
        self::assertSame(Dependency::REQ_ENABLECOLUMNS, $dependency->getRequirement());
    }

    public function testGetStateRecursiveReturnsRecordStateIfRecordHasNoChildren(): void
    {
        $localProps = $foreignProps = [
            'uid' => 123,
            'pid' => 7,
        ];
        $testRecord = $this->createRecord('foo', 123, $localProps, $foreignProps, []);
        $state = $testRecord->getStateRecursive();
        self::assertSame(Record::S_UNCHANGED, $state);
    }

    public function testGetStateRecursiveReturnsChangedIfChangedChildIsPresent(): void
    {
        $localProps = $foreignProps = [
            'uid' => 123,
            'pid' => 7,
        ];
        $testRecord = $this->createRecord('foo', 123, $localProps, $foreignProps, []);
        $testRecord->addChild($this->createRecord('foo', 235, $localProps, [], []));
        $state = $testRecord->getStateRecursive();
        self::assertSame(Record::S_CHANGED, $state);
    }

    public function testGetStateRecursiveReturnsChangedIfChangedNestedChildIsPresent(): void
    {
        $localProps = $foreignProps = [
            'uid' => 123,
            'pid' => 7,
        ];
        $testRecord = $this->createRecord('foo', 123, $localProps, $foreignProps, []);
        $child = $this->createRecord('foo', 235, $localProps, $foreignProps, []);
        $nestedChild = $this->createRecord('foo', 486, $localProps, [], []);
        $child->addChild($nestedChild);
        $testRecord->addChild($child);
        $state = $testRecord->getStateRecursive();
        self::assertSame(Record::S_CHANGED, $state);
    }

    public function testGetAllDependenciesReturnsDependenciesOfAllChildRecords(): void
    {
        $localProps = $foreignProps = [
            'uid' => 123,
            'pid' => 7,
        ];
        $childLocalProps = $childForeignProps = [
            'uid' => 123,
            'pid' => 18,
        ];
        $testRecord = $this->createRecord('foo', 123, $localProps, $foreignProps, []);
        $child = $this->createRecord('foo', 235, $childLocalProps, $childForeignProps, []);
        $testRecord->addChild($child);
        $dependencies = $testRecord->getAllDependencies();
        /** @var Dependency[] $dependencies */
        $dependencies = iterator_to_array($dependencies, false);
        self::assertCount(4, $dependencies);
        self::assertSame('pages', $dependencies[0]->getClassification());
        self::assertSame(['uid' => 7], $dependencies[0]->getProperties());
        self::assertSame(Dependency::REQ_EXISTING, $dependencies[0]->getRequirement());
        self::assertSame('pages', $dependencies[1]->getClassification());
        self::assertSame(['uid' => 7], $dependencies[1]->getProperties());
        self::assertSame(Dependency::REQ_ENABLECOLUMNS, $dependencies[1]->getRequirement());
        self::assertSame('pages', $dependencies[2]->getClassification());
        self::assertSame(['uid' => 18], $dependencies[2]->getProperties());
        self::assertSame(Dependency::REQ_EXISTING, $dependencies[2]->getRequirement());
        self::assertSame('pages', $dependencies[3]->getClassification());
        self::assertSame(['uid' => 18], $dependencies[3]->getProperties());
        self::assertSame(Dependency::REQ_ENABLECOLUMNS, $dependencies[3]->getRequirement());
    }

    protected function createRecord(
        string $table,
        int $id,
        array $localProps,
        array $foreignProps,
        array $ignoredProps
    ): AbstractDatabaseRecord {
        $GLOBALS['TCA'][$table]['ctrl'][AbstractDatabaseRecord::CTRL_PROP_LANGUAGE_FIELD] = 'sys_lang';
        $GLOBALS['TCA'][$table]['ctrl'][AbstractDatabaseRecord::CTRL_PROP_TRANS_ORIG_POINTER_FIELD] = 'sys_lang_parent';
        $GLOBALS['TCA'][$table]['ctrl'][AbstractDatabaseRecord::CTRL_PROP_ENABLECOLUMNS] = [
            'enable1' => 'enable_1',
            'enable2' => 'enable_2',
        ];

        return new class($table, $id, $localProps, $foreignProps, $ignoredProps) extends AbstractDatabaseRecord {
            protected int $id;

            public function __construct(
                string $table,
                int $id,
                array $localProps,
                array $foreignProps,
                array $ignoredProps
            ) {
                $this->table = $table;
                $this->id = $id;

                $this->localProps = $localProps;
                $this->foreignProps = $foreignProps;
                $this->ignoredProps = $ignoredProps;

                $this->state = $this->calculateState();
                $this->dependencies = $this->calculateDependencies();
            }

            public function getId(): int
            {
                return $this->id;
            }
        };
    }
}
