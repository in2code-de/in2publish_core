<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Record\Model;

use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Dependency;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\Core\Record\Model\TtContentDatabaseRecord;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;

#[CoversMethod(TtContentDatabaseRecord::class, '__construct')]
#[CoversMethod(TtContentDatabaseRecord::class, 'getId')]
#[CoversMethod(TtContentDatabaseRecord::class, 'getClassification')]
#[CoversMethod(TtContentDatabaseRecord::class, 'getLocalProps')]
#[CoversMethod(TtContentDatabaseRecord::class, 'getForeignProps')]
#[CoversMethod(TtContentDatabaseRecord::class, 'getDependencies')]
#[CoversMethod(TtContentDatabaseRecord::class, 'calculateDependencies')]
#[CoversMethod(TtContentDatabaseRecord::class, 'calculateShortcutDependencies')]
#[CoversMethod(TtContentDatabaseRecord::class, 'isBeingDeleted')]
class TtContentDatabaseRecordTest extends UnitTestCase
{
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
        $this->assertSame(Dependency::REQ_FULL_PUBLISHED_OR_LOCALLY_DELETED, $dependency1->getRequirement());

        $dependency2 = $ttContentDatabaseRecord->calculateDependencies()[1];
        $this->assertInstanceOf(Dependency::class, $dependency2);
        $this->assertSame('table_bar', $dependency2->getClassification());
        $this->assertSame(['uid' => '2'], $dependency2->getProperties());
        $this->assertSame(Dependency::REQ_FULL_PUBLISHED_OR_LOCALLY_DELETED, $dependency2->getRequirement());

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

    public function testSoftDeletedShortcutHasFulfilledDependenciesEvenWhenTargetDoesNotExist(): void
    {
        // Scenario: a shortcut content element is soft-deleted (deleted=1 local, deleted=0 foreign).
        // Its referenced target no longer exists in the DB.
        // Publishing the deletion must not be blocked by the missing shortcut target.
        $GLOBALS['TCA']['table_foo']['ctrl']['delete'] = 'deleted';

        $localProps = ['CType' => 'shortcut', 'records' => 'table_bar_1', 'deleted' => 1];
        $foreignProps = ['CType' => 'shortcut', 'records' => 'table_bar_1', 'deleted' => 0];

        $record = new TtContentDatabaseRecord('table_foo', 42, $localProps, $foreignProps, []);

        self::assertSame(Record::S_SOFT_DELETED, $record->getState());

        $emptyCollection = new RecordCollection();
        foreach ($record->getDependencies() as $dependency) {
            $dependency->fulfill($emptyCollection);
        }

        foreach ($record->getDependencies() as $dependency) {
            self::assertTrue(
                $dependency->isFulfilled(),
                'Shortcut dependency of a soft-deleted record must always be fulfilled',
            );
        }
    }

    public function testActiveShortcutWithMissingTargetIsNotBlocked(): void
    {
        // Scenario: an active shortcut references a target which has been deleted and published, so the
        // target exists in neither database. TYPO3 keeps the dangling reference in the records field.
        // Publishing must not be blocked: there is nothing left to publish for the target and no
        // editor action could resolve it.
        $localProps = ['CType' => 'shortcut', 'records' => 'table_bar_1'];
        $foreignProps = ['CType' => 'shortcut', 'records' => 'table_bar_1'];

        $record = new TtContentDatabaseRecord('table_foo', 42, $localProps, $foreignProps, []);

        self::assertSame(Record::S_UNCHANGED, $record->getState());

        $emptyCollection = new RecordCollection();
        foreach ($record->getDependencies() as $dependency) {
            $dependency->fulfill($emptyCollection);
        }

        foreach ($record->getDependencies() as $dependency) {
            self::assertTrue(
                $dependency->isFulfilled(),
                'A shortcut dependency with a target missing in both databases must not block publishing',
            );
        }
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: array<string, mixed>, 2: string}>
     */
    public static function unpublishedTargetDeletionDataProvider(): array
    {
        return [
            'soft deleted on local, still present on foreign' => [
                ['uid' => 1, 'deleted' => 1],
                ['uid' => 1, 'deleted' => 0],
                Record::S_SOFT_DELETED,
            ],
            'hard deleted on local, still present on foreign' => [
                [],
                ['uid' => 1, 'deleted' => 0],
                Record::S_DELETED,
            ],
        ];
    }

    #[DataProvider('unpublishedTargetDeletionDataProvider')]
    public function testShortcutIsNotBlockedByATargetWhoseDeletionIsNotPublishedYet(
        array $targetLocalProps,
        array $targetForeignProps,
        string $expectedTargetState
    ): void {
        // Scenario: the shortcut target has been deleted on local, but that deletion has not been
        // published yet. The editor can not see the target anymore, so the demand to publish it first
        // is not actionable. TYPO3 tolerates a missing shortcut target without showing an error.
        $GLOBALS['TCA']['table_bar']['ctrl']['delete'] = 'deleted';

        $shortcut = new TtContentDatabaseRecord(
            'table_foo',
            42,
            ['CType' => 'shortcut', 'records' => 'table_bar_1'],
            ['CType' => 'shortcut', 'records' => 'table_bar_1'],
            [],
        );
        $target = new DatabaseRecord('table_bar', 1, $targetLocalProps, $targetForeignProps, []);

        self::assertSame($expectedTargetState, $target->getState());

        $recordCollection = new RecordCollection([$target]);
        foreach ($shortcut->getDependencies() as $dependency) {
            $dependency->fulfill($recordCollection);
            self::assertTrue(
                $dependency->isFulfilled(),
                'A shortcut target which no longer exists on local must not block publishing',
            );
        }
    }

    public function testShortcutIsStillBlockedByAnUnpublishedTarget(): void
    {
        // Scenario: the shortcut target is new and not deleted. Publishing the shortcut without the
        // target must keep being blocked, because the editor can resolve this by publishing the target.
        $GLOBALS['TCA']['table_bar']['ctrl']['delete'] = 'deleted';

        $shortcut = new TtContentDatabaseRecord(
            'table_foo',
            42,
            ['CType' => 'shortcut', 'records' => 'table_bar_1'],
            ['CType' => 'shortcut', 'records' => 'table_bar_1'],
            [],
        );
        $target = new DatabaseRecord('table_bar', 1, ['uid' => 1, 'deleted' => 0], [], []);

        self::assertSame(Record::S_ADDED, $target->getState());

        $recordCollection = new RecordCollection([$target]);
        $hasUnfulfilled = false;
        foreach ($shortcut->getDependencies() as $dependency) {
            $dependency->fulfill($recordCollection);
            if ($dependency->isFulfilled() === false) {
                $hasUnfulfilled = true;
            }
        }
        self::assertTrue(
            $hasUnfulfilled,
            'A shortcut pointing to an unpublished, existing target must still block publishing',
        );
    }

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
