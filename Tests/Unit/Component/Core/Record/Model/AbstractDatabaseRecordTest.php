<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Record\Model;

use In2code\In2publishCore\Component\Core\Reason\Reason;
use In2code\In2publishCore\Component\Core\Record\Iterator\RecordIterator;
use In2code\In2publishCore\Component\Core\Record\Model\AbstractDatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Dependency;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\Core\Record\Model\TtContentDatabaseRecord;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Event\CollectReasonsWhyTheRecordIsNotPublishable;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function iterator_to_array;

#[CoversMethod(AbstractDatabaseRecord::class, 'calculateDependencies')]
#[CoversMethod(AbstractDatabaseRecord::class, 'isBeingDeleted')]
#[CoversMethod(AbstractDatabaseRecord::class, 'calculateLanguageDependencies')]
#[CoversMethod(AbstractDatabaseRecord::class, 'calculateParentRecordDependencies')]
#[CoversMethod(AbstractDatabaseRecord::class, 'getStateRecursive')]
#[CoversMethod(AbstractDatabaseRecord::class, 'getAllDependencies')]
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
        self::assertSame(Dependency::REQ_CONSISTENT_EXISTENCE, $dependency->getRequirement());
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

    public function testGetDependencyTree(): void
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
        $tree = $testRecord->getDependencyTree();

        self::assertArrayHasKey('foo', $tree);
        self::assertArrayHasKey(123, $tree['foo']);
        self::assertArrayHasKey('dependencies', $tree['foo'][123]);
        self::assertArrayHasKey(0, $tree['foo'][123]['dependencies']);
        self::assertArrayHasKey(1, $tree['foo'][123]['dependencies']);
        self::assertInstanceOf(Dependency::class, $tree['foo'][123]['dependencies'][0]);
        self::assertInstanceOf(Dependency::class, $tree['foo'][123]['dependencies'][1]);
        self::assertArrayHasKey('children', $tree['foo'][123]);
        self::assertArrayHasKey('foo', $tree['foo'][123]['children']);
        self::assertArrayHasKey(235, $tree['foo'][123]['children']['foo']);
        self::assertArrayHasKey('dependencies', $tree['foo'][123]['children']['foo'][235]);
        self::assertArrayHasKey(0, $tree['foo'][123]['children']['foo'][235]['dependencies']);
        self::assertArrayHasKey(1, $tree['foo'][123]['children']['foo'][235]['dependencies']);
        self::assertInstanceOf(Dependency::class, $tree['foo'][123]['children']['foo'][235]['dependencies'][0]);
        self::assertInstanceOf(Dependency::class, $tree['foo'][123]['children']['foo'][235]['dependencies'][1]);
    }

    public function testGetUnfulfilledDependenciesPositiveCheck(): void
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

        $recordCollection = new RecordCollection([$testRecord, $child]);

        $this->fulfillDependencies($testRecord, $recordCollection);

        $eventDispatcherMock = $this->createMock(EventDispatcher::class);
        $eventDispatcherMock->method('dispatch')->willReturnCallback(
            static function (CollectReasonsWhyTheRecordIsNotPublishable $event): CollectReasonsWhyTheRecordIsNotPublishable {
                return $event;
            },
        );
        GeneralUtility::setSingletonInstance(EventDispatcherInterface::class, $eventDispatcherMock);

        self::assertTrue($testRecord->hasUnfulfilledDependenciesRecursively());
    }

    public function testGetUnfulfilledDependenciesNegativeCheck(): void
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

        $pageRecordOne = new DatabaseRecord('pages', 7, ['uid' => 7], ['uid' => 7], []);
        $pageRecordTwo = new DatabaseRecord('pages', 18, ['uid' => 18], ['uid' => 18], []);

        $recordCollection = new RecordCollection([$testRecord, $child, $pageRecordOne, $pageRecordTwo]);

        $this->fulfillDependencies($testRecord, $recordCollection);

        $eventDispatcherMock = $this->createMock(EventDispatcher::class);
        $eventDispatcherMock->method('dispatch')->willReturnCallback(
            static function (CollectReasonsWhyTheRecordIsNotPublishable $event): CollectReasonsWhyTheRecordIsNotPublishable {
                return $event;
            },
        );
        GeneralUtility::setSingletonInstance(EventDispatcherInterface::class, $eventDispatcherMock);

        self::assertFalse($testRecord->hasUnfulfilledDependenciesRecursively());
    }

    public function testIsPublishableReturnsFalseWhenDependenciesAreUnfulfilled(): void
    {
        $localProps = $foreignProps = [
            'uid' => 123,
            'pid' => 7,
        ];
        $testRecord = $this->createRecord('foo', 123, $localProps, $foreignProps, []);

        $recordCollection = new RecordCollection([$testRecord]);

        $this->fulfillDependencies($testRecord, $recordCollection);

        $eventDispatcherMock = $this->createMock(EventDispatcher::class);
        $eventDispatcherMock->method('dispatch')->willReturnCallback(
            static function (CollectReasonsWhyTheRecordIsNotPublishable $event): CollectReasonsWhyTheRecordIsNotPublishable {
                return $event;
            },
        );
        GeneralUtility::setSingletonInstance(EventDispatcherInterface::class, $eventDispatcherMock);

        self::assertFalse($testRecord->hasReasonsWhyTheRecordIsNotPublishable());
        self::assertTrue($testRecord->hasUnfulfilledDependenciesRecursively());
        self::assertFalse($testRecord->isPublishable());
    }

    public function testIsPublishableReturnsFalseWhenRecordHasReasons(): void
    {
        $localProps = $foreignProps = [
            'uid' => 123,
            'pid' => 7,
        ];
        $testRecord = $this->createRecord('foo', 123, $localProps, $foreignProps, []);

        $pageRecordOne = new DatabaseRecord('pages', 7, ['uid' => 7], ['uid' => 7], []);
        $recordCollection = new RecordCollection([$testRecord, $pageRecordOne]);

        $this->fulfillDependencies($testRecord, $recordCollection);

        $eventDispatcherMock = $this->createMock(EventDispatcher::class);
        $eventDispatcherMock->method('dispatch')->willReturnCallback(
            static function (CollectReasonsWhyTheRecordIsNotPublishable $event): CollectReasonsWhyTheRecordIsNotPublishable {
                $event->addReason(new Reason('foo'));
                return $event;
            },
        );
        GeneralUtility::setSingletonInstance(EventDispatcherInterface::class, $eventDispatcherMock);

        self::assertTrue($testRecord->hasReasonsWhyTheRecordIsNotPublishable());
        self::assertFalse($testRecord->hasUnfulfilledDependenciesRecursively());
        self::assertFalse($testRecord->isPublishable());
    }

    public function testIsPublishableReturnsTrueIgnoringUnreachableDependencies(): void
    {
        $localProps = $foreignProps = [
            'uid' => 123,
            'pid' => 7,
            'CType' => 'shortcut',
            'records' => 'tt_content_15',
        ];
        $testRecord = new TtContentDatabaseRecord('tt_content', 123, $localProps, $foreignProps, []);

        $GLOBALS['TCA']['tt_content']['ctrl']['languageField'] = 'sys_language';

        $referencedRecord = new TtContentDatabaseRecord(
            'tt_content',
            15,
            ['uid' => 15, 'sys_language' => 1],
            [],
            [],
        );
        $pageRecordOne = new DatabaseRecord('pages', 7, ['uid' => 7], ['uid' => 7], []);
        $recordCollection = new RecordCollection([$testRecord, $pageRecordOne, $referencedRecord]);

        $this->fulfillDependencies($testRecord, $recordCollection);

        $eventDispatcherMock = $this->createMock(EventDispatcher::class);
        $eventDispatcherMock->method('dispatch')->willReturnCallback(
            static function (CollectReasonsWhyTheRecordIsNotPublishable $event): CollectReasonsWhyTheRecordIsNotPublishable {
                return $event;
            },
        );
        GeneralUtility::setSingletonInstance(EventDispatcherInterface::class, $eventDispatcherMock);

        $backendUserMock = $this->createMock(BackendUserAuthentication::class);
        $backendUserMock->method('isAdmin')->willReturn(false);
        $backendUserMock->method('checkLanguageAccess')->willReturn(false);

        $GLOBALS['BE_USER'] = $backendUserMock;

        self::assertFalse($testRecord->hasReasonsWhyTheRecordIsNotPublishable());
        self::assertTrue($testRecord->hasUnfulfilledDependenciesRecursively());
        self::assertFalse($testRecord->isPublishable());
        self::assertTrue($testRecord->isPublishableIgnoringUnreachableDependencies());
    }

    public function testSoftDeletedTranslationWithNonExistentLanguageParentHasFulfilledDependencies(): void
    {
        // Scenario: a translated record was soft-deleted locally (deleted=1),
        // but its language parent was hard-deleted on foreign.
        // The CP must not block publishing the deletion.
        $GLOBALS['TCA']['foo']['ctrl']['delete'] = 'deleted';

        $localProps = [
            'uid' => 123,
            'sys_lang' => 1,
            'sys_lang_parent' => 99, // parent no longer exists in the DB
            'deleted' => 1,
        ];
        $foreignProps = [
            'uid' => 123,
            'sys_lang' => 1,
            'sys_lang_parent' => 99,
            'deleted' => 0,
        ];
        $testRecord = $this->createRecord('foo', 123, $localProps, $foreignProps, []);

        self::assertSame(Record::S_SOFT_DELETED, $testRecord->getState());

        // Fulfill with an empty collection — the language parent (uid=99) does not exist
        $emptyCollection = new RecordCollection();
        foreach ($testRecord->getDependencies() as $dependency) {
            $dependency->fulfill($emptyCollection);
        }

        self::assertFalse(
            $testRecord->hasUnfulfilledDependenciesRecursively(),
            'A soft-deleted translation must not be blocked by a non-existent language parent',
        );
    }

    public function testSoftDeletedRecordWithNonExistentPageHasFulfilledDependencies(): void
    {
        // Scenario: a content element was soft-deleted, its parent page was also deleted
        // and not yet published. The deletion of the content must still be publishable.
        $GLOBALS['TCA']['foo']['ctrl']['delete'] = 'deleted';

        $localProps = [
            'uid' => 123,
            'pid' => 99, // page no longer exists in the DB
            'deleted' => 1,
        ];
        $foreignProps = [
            'uid' => 123,
            'pid' => 99,
            'deleted' => 0,
        ];
        $testRecord = $this->createRecord('foo', 123, $localProps, $foreignProps, []);

        self::assertSame(Record::S_SOFT_DELETED, $testRecord->getState());

        $emptyCollection = new RecordCollection();
        foreach ($testRecord->getDependencies() as $dependency) {
            $dependency->fulfill($emptyCollection);
        }

        self::assertFalse(
            $testRecord->hasUnfulfilledDependenciesRecursively(),
            'A soft-deleted record must not be blocked by a non-existent parent page',
        );
    }

    public function testAddedAndLocallyDeletedTranslationHasFulfilledDependencies(): void
    {
        // Scenario: a translation was created locally (S_ADDED) and then soft-deleted before
        // its first publish. The CP must not block publishing
        $GLOBALS['TCA']['foo']['ctrl']['delete'] = 'deleted';

        $localProps = [
            'uid' => 9016,
            'sys_lang' => 1,
            'sys_lang_parent' => 9015, // parent no longer exists in DB
            'deleted' => 1,
        ];
        // Foreign does not have this record at all (never published)
        $foreignProps = [];
        $testRecord = $this->createRecord('foo', 9016, $localProps, $foreignProps, []);

        self::assertSame(Record::S_ADDED, $testRecord->getState());

        $emptyCollection = new RecordCollection();
        foreach ($testRecord->getDependencies() as $dependency) {
            $dependency->fulfill($emptyCollection);
        }

        self::assertFalse(
            $testRecord->hasUnfulfilledDependenciesRecursively(),
            'A locally-deleted (S_ADDED+deleted=1) translation must not be blocked by a non-existent language parent',
        );
    }

    public function testActiveTranslationWithNonExistentLanguageParentIsStillBlocked(): void
    {
        // Scenario: an active (non-deleted) translation with a non-existent language parent.
        // Publishing must be blocked because of the missing language parent.
        $localProps = [
            'uid' => 123,
            'sys_lang' => 1,
            'sys_lang_parent' => 99, // parent does not exist
            'deleted' => 0,
        ];
        $foreignProps = [
            'uid' => 123,
            'sys_lang' => 1,
            'sys_lang_parent' => 99,
            'deleted' => 0,
        ];
        $testRecord = $this->createRecord('foo', 123, $localProps, $foreignProps, []);

        $emptyCollection = new RecordCollection();
        foreach ($testRecord->getDependencies() as $dependency) {
            $dependency->fulfill($emptyCollection);
        }

        self::assertTrue(
            $testRecord->hasUnfulfilledDependenciesRecursively(),
            'An active translation with a missing language parent must still be blocked',
        );
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

        return new class ($table, $id, $localProps, $foreignProps, $ignoredProps) extends AbstractDatabaseRecord {
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

    public function fulfillDependencies(AbstractDatabaseRecord $testRecord, RecordCollection $recordCollection): void
    {
        $iterator = new RecordIterator();
        $iterator->recurse($testRecord, static function (Record $record) use ($recordCollection): void {
            $dependencies = $record->getDependencies();
            foreach ($dependencies as $dependency) {
                $dependency->fulfill($recordCollection);
            }
        });
    }
}
