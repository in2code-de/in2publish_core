<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Record\Model;

use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Dependency;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Tests\Unit\TestDataHandler;
use In2code\In2publishCore\Tests\UnitTestCase;
use ReflectionMethod;
use ReflectionProperty;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * @coversDefaultClass Dependency
 */
class DependencyTest extends UnitTestCase
{
    /**
     * @covers ::__construct
     */
    public function testDependencyCanBeInstantiated(): void
    {
        self::assertInstanceOf(
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
        $reflectionProperty = new ReflectionProperty(Dependency::class, 'supersededBy');
        $reflectionProperty->setAccessible(true);
        self::assertSame(
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
        self::assertSame(
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

        self::assertSame(
            '4711',
            $dependency2->getPropertiesAsUidOrString(),
        );
    }

    /**
     * @covers ::fulfill
     * @covers ::recordMatchesRequirements
     */
    public function testFulfillReturnsFalseIfRecordDoesNotExist(): void
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
        self::assertFalse($dependency->isFulfilled());

        $reflectionProperty = new ReflectionProperty(Dependency::class, 'reasons');
        $reflectionProperty->setAccessible(true);
        $reasons = $reflectionProperty->getValue($dependency);

        self::assertFalse($reasons->isEmpty());

        $expectedReasonLabel = "LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:record.reason.missing_dependency";
        $actualLabel = $reasons->getAll()[0]->getLabel();

        self::assertSame($expectedReasonLabel, $actualLabel);
    }

    /**
     * @covers ::fulfill
     * @covers ::recordMatchesRequirements
     */
    public function testFulfillReturnsTrueIfRecordIsUnchangedAndRequirementIsFullyPublished(): void
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
        self::assertTrue($dependency->isFulfilled());

        $reflectionProperty = new ReflectionProperty(Dependency::class, 'reasons');
        $reflectionProperty->setAccessible(true);
        $reasons = $reflectionProperty->getValue($dependency);

        self::assertTrue($reasons->isEmpty());
    }

    /**
     * @covers ::fulfill
     * @covers ::recordMatchesRequirements
     */
    public function testFulfillReturnsFalse(): void
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
        self::assertFalse($dependency->isFulfilled());

        $reflectionProperty = new ReflectionProperty(Dependency::class, 'reasons');
        $reflectionProperty->setAccessible(true);
        $reasons = $reflectionProperty->getValue($dependency);
        $expectedReasonLabel = 'Label for my dependency';
        $actualLabel = $reasons->getAll()[0]->getLabel();

        self::assertSame($expectedReasonLabel, $actualLabel);
    }

    /**
     * @covers ::areSupersededDependenciesFulfilled
     * @covers ::isSupersededByUnfulfilledDependency
     */
    public function testAreSupersededDependenciesFulfilled(): void
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

        $fulfilledSupersedingDependencies = new ReflectionProperty(
            $dependencyWithFulfilledDependencies,
            'supersededBy',
        );
        $fulfilledSupersedingDependencies->setAccessible(true);
        $fulfilledSupersedingDependencies->setValue($dependencyWithFulfilledDependencies, [$dependency1, $dependency2]);

        $unfulfilledSupersedingDependencies = new ReflectionProperty(
            $dependencyWithUnfulfilledDependencies,
            'supersededBy',
        );
        $unfulfilledSupersedingDependencies->setAccessible(true);
        $unfulfilledSupersedingDependencies->setValue(
            $dependencyWithUnfulfilledDependencies,
            [$dependency2, $dependency3],
        );

        $testMethodUnfulfilled = new ReflectionMethod(
            $dependencyWithUnfulfilledDependencies,
            'areSupersededDependenciesFulfilled',
        );
        $testMethodFulfilled = new ReflectionMethod(
            $dependencyWithFulfilledDependencies,
            'areSupersededDependenciesFulfilled',
        );

        // act
        $testMethodUnfulfilled->setAccessible(true);
        $actualResultUnfilled = $testMethodUnfulfilled->invoke($dependencyWithUnfulfilledDependencies);

        $testMethodFulfilled->setAccessible(true);
        $actualResultFulfilled = $testMethodFulfilled->invoke($dependencyWithFulfilledDependencies);

        // assert
        self::assertFalse($actualResultUnfilled);
        self::assertTrue($actualResultFulfilled);

        self::assertFalse($dependencyWithFulfilledDependencies->isSupersededByUnfulfilledDependency());
        self::assertTrue($dependencyWithUnfulfilledDependencies->isSupersededByUnfulfilledDependency());
    }

    /**
     * @covers ::isReachable
     */
    public function testIsReachableReturnsTrueForAdmins(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->method('isAdmin')->willReturn(true);
        $dataHandler = $this->createMock(DataHandler::class);
        $dataHandler->BE_USER = $backendUser;

        $record = new DatabaseRecord('foo', 1, [], [], []);
        $blockingRecord = new DatabaseRecord('bar', 2, ['uid' => 2], [], []);
        $dependency = new Dependency(
            $record,
            'bar',
            ['uid' => 2],
            Dependency::REQ_EXISTING,
            'Require bar 2',
            static fn(Record $record): array => [(string)$record],
        );
        $recordCollection = new RecordCollection([$record, $blockingRecord]);
        $dependency->fulfill($recordCollection);
        self::assertFalse($dependency->isFulfilled());
        self::assertTrue($dependency->isReachable($dataHandler));
    }

    /**
     * Dependencies to non-existent records will be dropped.
     * @see Dependency::isReachable
     *
     * @covers ::isReachable
     */
    public function testIsReachableReturnsTrueIfRecordDoesNotExist(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->method('isAdmin')->willReturn(false);
        $dataHandler = $this->createMock(DataHandler::class);
        $dataHandler->BE_USER = $backendUser;

        $record = new DatabaseRecord('foo', 1, [], [], []);
        $dependency = new Dependency(
            $record,
            'bar',
            ['uid' => 2],
            Dependency::REQ_EXISTING,
            'Require bar 2',
            static fn(string $string): string => $string,
        );
        $recordCollection = new RecordCollection([$record]);
        $dependency->fulfill($recordCollection);
        self::assertFalse($dependency->isFulfilled());
        self::assertTrue($dependency->isReachable($dataHandler));
    }

    /**
     * @covers ::isReachable
     */
    public function testIsReachableReturnsFalseIfEditorHasNoLanguageAccess(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->method('isAdmin')->willReturn(false);
        $backendUser->method('checkLanguageAccess')->with(1)->willReturn(false);
        $dataHandler = $this->createMock(DataHandler::class);
        $dataHandler->BE_USER = $backendUser;

        $record = new DatabaseRecord('foo', 1, [], [], []);

        $GLOBALS['TCA']['bar']['ctrl']['languageField'] = 'language';
        $translatedRecord = new DatabaseRecord(
            'bar',
            2,
            ['uid' => 2, 'language' => 1],
            ['uid' => 2, 'language' => 1],
            [],
        );
        $dependency = new Dependency(
            $record,
            'bar',
            ['uid' => 2],
            Dependency::REQ_EXISTING,
            'Require bar 2',
            static fn(string $string): string => $string,
        );
        $recordCollection = new RecordCollection([$record, $translatedRecord]);
        $dependency->fulfill($recordCollection);
        self::assertFalse($dependency->isReachable($dataHandler));
    }

    /**
     * @covers ::isReachable
     */
    public function testIsReachableReturnsFalseIfTableIsReadonly(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->method('isAdmin')->willReturn(false);
        $backendUser->expects($this->once())->method('checkLanguageAccess')->with(1)->willReturn(true);
        $dataHandler = $this->getMockBuilder(TestDataHandler::class)
                            ->disableOriginalConstructor()
                            ->onlyMethods(['checkModifyAccessList'])
                            ->getMock();
        $dataHandler->BE_USER = $backendUser;
        $GLOBALS['TCA']['bar']['ctrl']['readOnly'] = true;
        $dataHandler->expects($this->never())->method('checkModifyAccessList');

        $record = new DatabaseRecord('foo', 1, [], [], []);

        $GLOBALS['TCA']['bar']['ctrl']['languageField'] = 'language';

        $translatedRecord = new DatabaseRecord(
            'bar',
            2,
            ['uid' => 2, 'language' => 1],
            ['uid' => 2, 'language' => 1],
            [],
        );
        $dependency = new Dependency(
            $record,
            'bar',
            ['uid' => 2],
            Dependency::REQ_EXISTING,
            'Require bar 2',
            static fn(string $string): string => $string,
        );
        $recordCollection = new RecordCollection([$record, $translatedRecord]);
        $dependency->fulfill($recordCollection);
        self::assertFalse($dependency->isReachable($dataHandler));
    }

    /**
     * @covers ::isReachable
     */
    public function testIsReachableReturnsFalseIfEditorIsNotAllowedToAccessTheTargetTable(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        // Expect one invocation to ensure that it is not invoked after checkModifyAccessList()
        $backendUser->expects($this->once())->method('isAdmin')->willReturn(false);
        $backendUser->expects($this->once())->method('checkLanguageAccess')->with(1)->willReturn(true);
        $dataHandler = $this->getMockBuilder(TestDataHandler::class)
                            ->disableOriginalConstructor()
                            ->onlyMethods(['checkModifyAccessList'])
                            ->getMock();
        $dataHandler->BE_USER = $backendUser;
        $GLOBALS['TCA']['bar']['ctrl']['readOnly'] = false;
        $dataHandler->expects($this->once())->method('checkModifyAccessList')->willReturn(false);

        $record = new DatabaseRecord('foo', 1, [], [], []);

        $GLOBALS['TCA']['bar']['ctrl']['languageField'] = 'language';

        $translatedRecord = new DatabaseRecord(
            'bar',
            2,
            ['uid' => 2, 'language' => 1],
            ['uid' => 2, 'language' => 1],
            [],
        );
        $dependency = new Dependency(
            $record,
            'bar',
            ['uid' => 2],
            Dependency::REQ_EXISTING,
            'Require bar 2',
            static fn(string $string): string => $string,
        );
        $recordCollection = new RecordCollection([$record, $translatedRecord]);
        $dependency->fulfill($recordCollection);
        self::assertFalse($dependency->isReachable($dataHandler));
    }

    /**
     * @covers ::isReachable
     */
    public function testIsReachableReturnsTrueIfRequiredRecordIsOkay(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->method('isAdmin')->willReturn(false);
        $backendUser->method('checkLanguageAccess')->with(1)->willReturn(true);
        $dataHandler = $this->getMockBuilder(TestDataHandler::class)
                            ->disableOriginalConstructor()
                            ->onlyMethods(['checkModifyAccessList', 'isInWebMount'])
                            ->getMock();
        $dataHandler->BE_USER = $backendUser;
        $dataHandler->method('checkModifyAccessList')->willReturn(true);
        $dataHandler->method('isInWebMount')->willReturn(true);

        $record = new DatabaseRecord('foo', 1, [], [], []);

        $GLOBALS['TCA']['bar']['ctrl']['readOnly'] = false;
        $GLOBALS['TCA']['bar']['ctrl']['languageField'] = 'language';

        $translatedRecord = new DatabaseRecord(
            'bar',
            2,
            ['uid' => 2, 'pid' => 1, 'language' => 1],
            ['uid' => 2, 'pid' => 1, 'language' => 1],
            [],
        );
        $dependency = new Dependency(
            $record,
            'bar',
            ['uid' => 2],
            Dependency::REQ_EXISTING,
            'Require bar 2',
            static fn(string $string): string => $string,
        );
        $recordCollection = new RecordCollection([$record, $translatedRecord]);
        $dependency->fulfill($recordCollection);
        self::assertTrue($dependency->isReachable($dataHandler));
    }

    /**
     * @depends testIsReachableReturnsTrueIfRequiredRecordIsOkay
     * @covers ::isReachable
     */
    public function testIsReachableReturnsFalseIfRecordIsOnRootLevel(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->method('isAdmin')->willReturn(false);
        $backendUser->method('checkLanguageAccess')->with(1)->willReturn(true);
        $dataHandler = $this->getMockBuilder(TestDataHandler::class)
                            ->disableOriginalConstructor()
                            ->onlyMethods(['checkModifyAccessList', 'isInWebMount'])
                            ->getMock();
        $dataHandler->BE_USER = $backendUser;
        $GLOBALS['TCA']['bar']['ctrl']['readOnly'] = false;
        $dataHandler->method('checkModifyAccessList')->willReturn(true);
        $dataHandler->method('isInWebMount')->willReturn(true);

        $record = new DatabaseRecord('foo', 1, [], [], []);

        $GLOBALS['TCA']['bar']['ctrl']['languageField'] = 'language';
        $GLOBALS['TCA']['bar']['ctrl']['rootLevel'] = 1;

        $translatedRecord = new DatabaseRecord(
            'bar',
            2,
            ['uid' => 2, 'pid' => 0, 'language' => 1],
            ['uid' => 2, 'pid' => 0, 'language' => 1],
            [],
        );
        $dependency = new Dependency(
            $record,
            'bar',
            ['uid' => 2],
            Dependency::REQ_EXISTING,
            'Require bar 2',
            static fn(string $string): string => $string,
        );
        $recordCollection = new RecordCollection([$record, $translatedRecord]);
        $dependency->fulfill($recordCollection);
        self::assertFalse($dependency->isReachable($dataHandler));
    }

    /**
     * @depends testIsReachableReturnsTrueIfRequiredRecordIsOkay
     * @covers ::isReachable
     */
    public function testIsReachableReturnsTrueIfRecordIsOnRootLevelButRestrictionIsIgnored(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->method('isAdmin')->willReturn(false);
        $backendUser->method('checkLanguageAccess')->with(1)->willReturn(true);
        $dataHandler = $this->getMockBuilder(TestDataHandler::class)
                            ->disableOriginalConstructor()
                            ->onlyMethods(['checkModifyAccessList','isInWebMount'])
                            ->getMock();
        $dataHandler->BE_USER = $backendUser;
        $GLOBALS['TCA']['bar']['ctrl']['readOnly'] = false;
        $dataHandler->method('checkModifyAccessList')->willReturn(true);
        $dataHandler->method('isInWebMount')->willReturn(true);

        $record = new DatabaseRecord('foo', 1, [], [], []);

        $GLOBALS['TCA']['bar']['ctrl']['languageField'] = 'language';
        $GLOBALS['TCA']['bar']['ctrl']['rootLevel'] = 1;
        $GLOBALS['TCA']['bar']['ctrl']['security']['ignoreRootLevelRestriction'] = true;

        $translatedRecord = new DatabaseRecord(
            'bar',
            2,
            ['uid' => 2, 'pid' => 0, 'language' => 1],
            ['uid' => 2, 'pid' => 0, 'language' => 1],
            [],
        );
        $dependency = new Dependency(
            $record,
            'bar',
            ['uid' => 2],
            Dependency::REQ_EXISTING,
            'Require bar 2',
            static fn(string $string): string => $string,
        );
        $recordCollection = new RecordCollection([$record, $translatedRecord]);
        $dependency->fulfill($recordCollection);
        self::assertTrue($dependency->isReachable($dataHandler));
    }

    /**
     * @depends testIsReachableReturnsTrueIfRequiredRecordIsOkay
     * @covers ::isReachable
     */
    public function testIsReachableReturnsFalseIfRecordTableIsEditLocked(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->method('isAdmin')->willReturn(false);
        $backendUser->method('checkLanguageAccess')->with(1)->willReturn(true);
        $dataHandler = $this->getMockBuilder(TestDataHandler::class)
                            ->disableOriginalConstructor()
                            ->onlyMethods(['checkModifyAccessList', 'isInWebMount'])
                            ->getMock();
        $dataHandler->BE_USER = $backendUser;
        $GLOBALS['TCA']['bar']['ctrl']['readOnly'] = false;
        $dataHandler->method('checkModifyAccessList')->willReturn(true);
        $dataHandler->method('isInWebMount')->willReturn(true);

        $record = new DatabaseRecord('foo', 1, [], [], []);

        $GLOBALS['TCA']['bar']['ctrl']['languageField'] = 'language';
        $GLOBALS['TCA']['bar']['ctrl']['editlock'] = 'adminOnly';

        $translatedRecord = new DatabaseRecord(
            'bar',
            2,
            ['uid' => 2, 'pid' => 0, 'language' => 1, 'adminOnly' => true],
            ['uid' => 2, 'pid' => 0, 'language' => 1, 'adminOnly' => true],
            [],
        );
        $dependency = new Dependency(
            $record,
            'bar',
            ['uid' => 2],
            Dependency::REQ_EXISTING,
            'Require bar 2',
            static fn(string $string): string => $string,
        );
        $recordCollection = new RecordCollection([$record, $translatedRecord]);
        $dependency->fulfill($recordCollection);
        self::assertFalse($dependency->isReachable($dataHandler));
    }

    /**
     * @depends testIsReachableReturnsTrueIfRequiredRecordIsOkay
     * @covers ::isReachable
     */
    public function testIsReachableReturnsFalseIfRecordIsNotInWebMount(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->method('isAdmin')->willReturn(false);
        $backendUser->method('checkLanguageAccess')->with(1)->willReturn(true);
        $dataHandler = $this->getMockBuilder(TestDataHandler::class)
                            ->disableOriginalConstructor()
                            ->onlyMethods(['checkModifyAccessList', 'isInWebMount'])
                            ->getMock();
        $dataHandler->BE_USER = $backendUser;
        $GLOBALS['TCA']['bar']['ctrl']['readOnly'] = false;
        $dataHandler->method('checkModifyAccessList')->willReturn(true);
        $dataHandler->method('isInWebMount')->willReturn(false);

        $record = new DatabaseRecord('foo', 1, [], [], []);

        $GLOBALS['TCA']['bar']['ctrl']['languageField'] = 'language';

        $translatedRecord = new DatabaseRecord(
            'bar',
            2,
            ['uid' => 2, 'pid' => 1, 'language' => 1],
            ['uid' => 2, 'pid' => 1, 'language' => 1],
            [],
        );
        $dependency = new Dependency(
            $record,
            'bar',
            ['uid' => 2],
            Dependency::REQ_EXISTING,
            'Require bar 2',
            static fn(string $string): string => $string,
        );
        $recordCollection = new RecordCollection([$record, $translatedRecord]);
        $dependency->fulfill($recordCollection);
        self::assertFalse($dependency->isReachable($dataHandler));
    }

    /**
     * @depends testIsReachableReturnsTrueIfRequiredRecordIsOkay
     * @covers ::isReachable
     */
    public function testIsReachableReturnsTrueIfRecordIsNotInWebMountButRestrictionIsIgnored(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->method('isAdmin')->willReturn(false);
        $backendUser->method('checkLanguageAccess')->with(1)->willReturn(true);
        $dataHandler = $this->getMockBuilder(TestDataHandler::class)
                            ->disableOriginalConstructor()
                            ->onlyMethods(['checkModifyAccessList', 'isInWebMount'])
                            ->getMock();
        $dataHandler->BE_USER = $backendUser;
        $GLOBALS['TCA']['bar']['ctrl']['readOnly'] = false;
        $dataHandler->method('checkModifyAccessList')->willReturn(true);
        $dataHandler->method('isInWebMount')->willReturn(false);

        $record = new DatabaseRecord('foo', 1, [], [], []);

        $GLOBALS['TCA']['bar']['ctrl']['languageField'] = 'language';
        $GLOBALS['TCA']['bar']['ctrl']['security']['ignoreWebMountRestriction'] = true;

        $translatedRecord = new DatabaseRecord(
            'bar',
            2,
            ['uid' => 2, 'pid' => 1, 'language' => 1],
            ['uid' => 2, 'pid' => 1, 'language' => 1],
            [],
        );
        $dependency = new Dependency(
            $record,
            'bar',
            ['uid' => 2],
            Dependency::REQ_EXISTING,
            'Require bar 2',
            static fn(string $string): string => $string,
        );
        $recordCollection = new RecordCollection([$record, $translatedRecord]);
        $dependency->fulfill($recordCollection);
        self::assertTrue($dependency->isReachable($dataHandler));
    }
}
