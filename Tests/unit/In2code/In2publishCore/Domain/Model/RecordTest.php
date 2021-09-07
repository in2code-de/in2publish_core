<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\In2code\In2publishCore\Domain\Model;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use Codeception\Test\Unit;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Tests\UnitTester;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass \In2code\In2publishCore\Domain\Model\Record
 */
class RecordTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $this->tester->setUp();
    }

    protected function _after()
    {
        $this->tester->tearDown();
    }

    /**
     * @param mixed $getIgnoreFields
     * @param bool $isParentRecordDisabled
     *
     * @return Record
     */
    protected function getRecordStub($getIgnoreFields, bool $isParentRecordDisabled = false): Record
    {
        $stub = $this->getMockBuilder(Record::class)
                     ->setMethods(['getIgnoreFields', 'isParentDisabled'])
                     ->disableOriginalConstructor()
                     ->getMock();

        $stub->method('getIgnoreFields')->will($this->returnValue($getIgnoreFields));
        $stub->method('isParentDisabled')->will($this->returnValue($isParentRecordDisabled));

        return $stub;
    }

    /**
     * @covers ::__construct
     * @covers ::setDirtyProperties
     * @covers ::getDirtyProperties
     */
    public function testDirtyPropertiesAreCalculatedWhenConstructed()
    {
        $stub = $this->getRecordStub([]);

        $stub->__construct(
            'pages',
            ['flupp' => 'zupp', 'foo' => 'baz', 'bar' => 'boo'],
            ['flupp' => 'zupp', 'foo' => 'boo', 'bing' => 'bong'],
            [],
            []
        );

        $this->assertSame(['foo', 'bar', 'bing'], $stub->getDirtyProperties());
    }

    /**
     * @covers ::__construct
     * @covers ::setDirtyProperties
     * @covers ::getDirtyProperties
     */
    public function testIgnoredFieldsDoNotAppearInDirtyPropertiesList()
    {
        $stub = $this->getRecordStub(['foo']);

        $stub->__construct(
            'pages',
            ['flupp' => 'zupp', 'foo' => 'baz', 'bar' => 'boo'],
            ['flupp' => 'zupp', 'foo' => 'boo', 'bar' => 'baz', 'bing' => 'bong'],
            [],
            []
        );

        $this->assertSame(['bar', 'bing'], $stub->getDirtyProperties());
    }

    /**
     * @covers ::setDirtyProperties
     */
    public function testNoFieldsAreIgnoredWhenIgnoreFieldsIsInvalid()
    {
        $stub = $this->getRecordStub('buff');

        $stub->__construct(
            'pages',
            ['flupp' => 'zupp', 'foo' => 'baz', 'bar' => 'boo'],
            ['flupp' => 'zupp', 'foo' => 'boo', 'bar' => 'baz', 'bing' => 'bong'],
            [],
            []
        );

        $this->assertSame(['foo', 'bar', 'bing'], $stub->getDirtyProperties());
    }

    /**
     * @covers ::__construct
     * @covers ::setTableName
     * @covers ::getTableName
     */
    public function testTableNameIsSetInConstructor()
    {
        $stub = $this->getRecordStub([]);

        $stub->__construct('pages', [], [], [], []);

        $this->assertSame('pages', $stub->getTableName());
    }

    /**
     * @covers ::__construct
     * @covers ::isPagesTable
     * @covers ::setTableName
     */
    public function testIsPagesTableReturnsTrueIfTableIsPages()
    {
        $stub = $this->getRecordStub([]);

        $stub->__construct('pages', [], [], [], []);

        $this->assertTrue($stub->isPagesTable());
    }

    /**
     * @covers ::__construct
     * @covers ::isPagesTable
     * @covers ::setTableName
     */
    public function testIsPagesTableReturnsFalseIfTableIsNotPages()
    {
        $stub = $this->getRecordStub([]);

        $stub->__construct('tt_content', [], [], [], []);

        $this->assertFalse($stub->isPagesTable());
    }

    /**
     * @covers ::__construct
     * @covers ::isPagesTable
     * @covers ::setTableName
     * @covers ::localRecordExists
     * @covers ::foreignRecordExists
     * @covers ::isRecordRepresentByProperties
     * @covers ::setState
     */
    public function testStateOfRecordIsAddedIfOnlyLocalPropertiesAreSet()
    {
        $stub = $this->getRecordStub([]);

        $stub->__construct('tt_content', ['uid' => 1], [], [], []);

        $this->assertSame(Record::RECORD_STATE_ADDED, $stub->getState());
    }

    /**
     * @covers ::__construct
     * @covers ::calculateState
     * @covers ::getState
     * @covers ::localRecordExists
     * @covers ::foreignRecordExists
     * @covers ::isRecordRepresentByProperties
     * @covers ::setState
     */
    public function testStateOfRecordIsDeletedIfOnlyForeignPropertiesAreSet()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct('tt_content', [], ['uid' => 1], [], []);

        $this->assertSame(Record::RECORD_STATE_DELETED, $stub->getState());
    }

    /**
     * @covers ::calculateState
     * @covers ::isRecordRepresentByProperties
     * @covers ::setState
     * @covers ::isRecordMarkedAsDeletedByProperties
     */
    public function testPropertyArraysAreConsideredEmptyIfSpecificFieldsAreNotSet()
    {
        // no uid or uid_local/uid_foreign. this is not a valid record
        $stub = $this->getRecordStub([]);
        $stub->__construct('tt_content', ['foo' => 'bar'], [], [], []);
        $this->assertSame(Record::RECORD_STATE_UNCHANGED, $stub->getState());

        // uid_local + uid_foreign = valid
        $stub = $this->getRecordStub([]);
        $stub->__construct('tt_content', ['uid_local' => 'bar', 'uid_foreign' => 'baz'], [], [], []);
        $this->assertSame(Record::RECORD_STATE_ADDED, $stub->getState());

        // only uid_local not valid
        $stub = $this->getRecordStub([]);
        $stub->__construct('tt_content', ['uid_local' => 'bar'], [], [], []);
        $this->assertSame(Record::RECORD_STATE_UNCHANGED, $stub->getState());

        // only uid_foreign not valid
        $stub = $this->getRecordStub([]);
        $stub->__construct('tt_content', ['uid_foreign' => 'bar'], [], [], []);
        $this->assertSame(Record::RECORD_STATE_UNCHANGED, $stub->getState());
    }

    /**
     * @covers ::calculateState
     * @covers ::isRecordRepresentByProperties
     * @covers ::setState
     * @covers ::isLocalRecordDeleted
     * @covers ::isForeignRecordDeleted
     * @covers ::isRecordMarkedAsDeletedByProperties
     */
    public function testRecordIsMarkedAsDeletedDefinedByDeleteFieldFromTca()
    {
        // no uid or uid_local/uid_foreign. this is not a valid record
        $stub = $this->getRecordStub([]);
        $stub->__construct(
            'tt_content',
            ['uid' => 1, 'foo' => true],
            ['uid' => 1, 'foo' => false],
            ['ctrl' => ['delete' => 'foo']],
            []
        );
        $this->assertSame(Record::RECORD_STATE_DELETED, $stub->getState());
    }

    /**
     * @covers ::calculateState
     */
    public function testRecordIsMarkedAsChangedIfItExistsOnBothSidesAndPropertiesDiffer()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct('tt_content', ['uid' => 1, 'foo' => 'bar'], ['uid' => 1, 'foo' => 'baz'], [], []);
        $this->assertSame(Record::RECORD_STATE_CHANGED, $stub->getState());
    }

    /**
     * @covers ::calculateState
     */
    public function testSysFileRecordsAreMovedIfIdentifiersDifferInsteadOfChanged()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct('sys_file', ['identifier' => 'bar'], ['identifier' => 'baz'], [], []);
        $this->assertSame(Record::RECORD_STATE_MOVED, $stub->getState());
    }

    /**
     * @covers ::calculateState
     */
    public function testSysFileRecordsAreTreatedAsNormalRecordsIfIdentifierPropertiesDoNotDiffer()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct('sys_file', ['identifier' => 'bar'], ['identifier' => 'bar'], [], []);
        $this->assertSame(Record::RECORD_STATE_UNCHANGED, $stub->getState());
    }

    /**
     * @covers ::isRecordRepresentByProperties
     */
    public function testRecordIsValidIfTableNameIsFolderAndNameIsSet()
    {
        // no uid or uid_local/uid_foreign. this is not a valid record
        $stub = $this->getRecordStub([]);
        $stub->__construct('folders', ['name' => 'bar'], ['name' => 'baz'], [], []);
        $this->assertSame(Record::RECORD_STATE_CHANGED, $stub->getState());
    }

    /**
     * @covers ::isRecordRepresentByProperties
     */
    public function testRecordIsInvalidIfTableNameIsFolderAndNameIsNotSet()
    {
        // no uid or uid_local/uid_foreign. this is not a valid record
        $stub = $this->getRecordStub([]);
        $stub->__construct('folders', ['name' => 'bar'], [], [], []);
        $this->assertSame(Record::RECORD_STATE_ADDED, $stub->getState());
    }

    /**
     * @covers ::createCombinedIdentifier
     */
    public function testCreateCombinedIdentifierPrefersLocalPropertiesArray()
    {
        $this->assertSame(
            'boo,baz',
            Record::createCombinedIdentifier(
                ['uid_local' => 'boo', 'uid_foreign' => 'baz'],
                ['uid_local' => 'foo', 'uid_foreign' => 'faz']
            )
        );
    }

    /**
     * @covers ::createCombinedIdentifier
     */
    public function testCreateCombinedIdentifierFallsBackToForeignPropertiesArray()
    {
        $this->assertSame(
            'foo,faz',
            Record::createCombinedIdentifier(
                [false, 1, 'uid_local' => 'stub'],
                ['uid_local' => 'foo', 'uid_foreign' => 'faz']
            )
        );
    }

    /**
     * @covers ::createCombinedIdentifier
     */
    public function testCreateCombinedIdentifierReturnsEmptyStringIfValuesAreMissing()
    {
        $this->assertSame(
            '',
            Record::createCombinedIdentifier(
                ['uid_local' => 'stub'],
                ['uid_foreign' => 'faz']
            )
        );
    }

    /**
     * @covers ::splitCombinedIdentifier
     */
    public function testSplitCombinedIdentifierReturnsArrayWithExpectedValues()
    {
        $this->assertSame(
            [
                'uid_local' => 'stub',
                'uid_foreign' => 'faz',
            ],
            Record::splitCombinedIdentifier('stub,faz')
        );
    }

    /**
     * @covers ::splitCombinedIdentifier
     */
    public function testSplitCombinedIdentifierReturnsEmptyArrayIfCombinedIdentifierIsInvalid()
    {
        $this->assertSame([], Record::splitCombinedIdentifier('stub'));
    }

    /**
     * @covers ::splitCombinedIdentifier
     */
    public function testSplitCombinedIdentifierIgnoresTrailingValues()
    {
        $this->assertSame(
            [
                'uid_local' => 'stub',
                'uid_foreign' => 'flub',
            ],
            Record::splitCombinedIdentifier('stub,flub,club')
        );
    }

    /**
     * @covers ::setParentRecord
     * @covers ::getParentRecord
     */
    public function testSetParentRecordSetsParentRecord()
    {
        $root = $this->getRecordStub([]);
        $root->__construct('pages', [], [], [], []);

        $content = $this->getRecordStub([]);
        $content->__construct('tt_content', [], [], [], []);

        $content->setParentRecord($root);

        $this->assertSame($root, $content->getParentRecord());
    }

    /**
     * @covers ::setParentRecord
     * @covers ::getParentRecord
     * @covers ::lockParentRecord
     * @covers ::isParentRecordLocked
     */
    public function testSetParentRecordNotSetsParentRecordIfParentRecordIsLocked()
    {
        $root = $this->getRecordStub([]);
        $root->__construct('pages', [], [], [], []);

        $content = $this->getRecordStub([]);
        $content->__construct('tt_content', [], [], [], []);

        $content->lockParentRecord();
        $this->assertTrue($content->isParentRecordLocked());

        $content->setParentRecord($root);

        $this->assertNull($content->getParentRecord());
    }

    /**
     * @covers ::hasLocalProperty
     */
    public function testHasLocalPropertyReturnsTrueIfPropertyIsSet()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct('pages', ['baz' => 'inga'], [], [], []);

        $this->assertTrue($stub->hasLocalProperty('baz'));
    }

    /**
     * @covers ::getLocalProperty
     */
    public function testGetLocalPropertyReturnsLocalProperty()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct('pages', ['baz' => 'inga'], [], [], []);

        $this->assertSame('inga', $stub->getLocalProperty('baz'));
    }

    /**
     * @covers ::getLocalProperty
     */
    public function testGetLocalPropertyReturnsNullIfLocalPropertyIsNotSet()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct('pages', ['baz' => 'inga'], [], [], []);

        $this->assertNull($stub->getLocalProperty('foo'));
    }

    /**
     * @covers ::getLocalProperties
     */
    public function testGetLocalPropertiesReturnsAllLocalProperties()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct('pages', ['baz' => 'inga', 'foo' => 'boo'], [], [], []);

        $this->assertSame(['baz' => 'inga', 'foo' => 'boo'], $stub->getLocalProperties());
    }

    /**
     * @covers ::setLocalProperties
     * @covers ::getLocalProperties
     */
    public function testSetLocalPropertiesSetsLocalProperties()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct('pages', ['foo' => 'boo'], [], [], []);

        $stub->setLocalProperties(['baz' => 'inga']);

        $this->assertSame(['baz' => 'inga'], $stub->getLocalProperties());
    }

    /**
     * @covers ::setForeignProperties
     * @covers ::getForeignProperties
     */
    public function testSetForeignPropertiesSetsForeignProperties()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct('pages', [], ['foo' => 'boo'], [], []);

        $stub->setForeignProperties(['baz' => 'inga']);

        $this->assertSame(['baz' => 'inga'], $stub->getForeignProperties());
    }

    /**
     * @covers ::setLocalProperties
     */
    public function testSetLocalPropertiesAllowsChaining()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct('pages', [], [], [], []);

        $this->assertSame(
            $stub,
            $stub->setLocalProperties([]),
            '[!!!] \In2code\In2publishCore\Domain\Model\Record::setLocalProperties must allow chaining'
        );
    }

    /**
     * @covers ::setForeignProperties
     */
    public function testSetForeignPropertiesAllowsChaining()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct('pages', [], [], [], []);

        $this->assertSame(
            $stub,
            $stub->setForeignProperties([]),
            '[!!!] \In2code\In2publishCore\Domain\Model\Record::setForeignProperties must allow chaining'
        );
    }

    /**
     * @covers ::getForeignProperties
     */
    public function testGetForeignPropertiesReturnsAllForeignProperties()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct('pages', [], ['foo' => 'boo'], [], []);

        $this->assertSame(['foo' => 'boo'], $stub->getForeignProperties());
    }

    /**
     * @covers ::getForeignProperty
     */
    public function testGetForeignPropertyReturnsForeignProperty()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct('pages', ['foo' => 'baa'], ['foo' => 'boo'], [], []);

        $this->assertSame('boo', $stub->getForeignProperty('foo'));
    }

    /**
     * @covers ::getForeignProperty
     */
    public function testGetForeignPropertyReturnsNullIfForeignPropertyIsNotSet()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct('pages', ['foo' => 'baa'], ['baz' => 'boo'], [], []);

        $this->assertNull($stub->getForeignProperty('foo'));
    }

    /**
     * @covers ::hasForeignProperty
     */
    public function testHasForeignPropertyReturnsFalseIfForeignPropertyIsNotSet()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct('pages', ['foo' => 'baa'], ['baz' => 'boo'], [], []);

        $this->assertFalse($stub->hasForeignProperty('foo'));
    }

    /**
     * @covers ::hasForeignProperty
     */
    public function testHasForeignPropertyReturnsTrueIfForeignPropertyIsSet()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct('pages', ['foo' => 'baa'], ['baz' => 'boo'], [], []);

        $this->assertTrue($stub->hasForeignProperty('baz'));
    }

    /**
     * @covers ::getIdentifier
     * @depends testHasLocalPropertyReturnsTrueIfPropertyIsSet
     * @depends testGetLocalPropertyReturnsLocalProperty
     */
    public function testGetIdentifierPrefersLocalUid()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct('pages', ['uid' => 1], ['uid' => 4], [], []);

        $this->assertSame(1, $stub->getIdentifier());
    }

    /**
     * @covers ::getIdentifier
     * @depends testHasForeignPropertyReturnsTrueIfForeignPropertyIsSet
     * @depends testGetForeignPropertyReturnsForeignProperty
     */
    public function testGetIdentifierReturnsForeignUidIfLocalUidIsNotSet()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct('pages', [], ['uid' => 4], [], []);

        $this->assertSame(4, $stub->getIdentifier());
    }

    /**
     * @covers ::getIdentifier
     * @depends testCreateCombinedIdentifierPrefersLocalPropertiesArray
     */
    public function testGetIdentifierReturnsCombinedIdentifierForLocalPropertiesIfNoUidIsSet()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct(
            'pages',
            ['uid_local' => 7, 'uid_foreign' => 2],
            ['uid_local' => 1, 'uid_foreign' => 4],
            [],
            []
        );

        $this->assertSame('7,2', $stub->getIdentifier());
    }

    /**
     * @covers ::getIdentifier
     * @depends testCreateCombinedIdentifierFallsBackToForeignPropertiesArray
     */
    public function testGetIdentifierReturnsCombinedIdentifierForForeignPropertiesIfNeitherLocalUidNorMmUidsAreSet()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct('pages', [], ['uid_local' => 1, 'uid_foreign' => 4], [], []);

        $this->assertSame('1,4', $stub->getIdentifier());
    }

    /**
     * @covers ::getIdentifier
     */
    public function testGetIdentifierReturnsCombinedIdentifierIfLocalUidAndForeignMmUidsAreSet()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct('pages', ['uid' => 3], ['uid_local' => 1, 'uid_foreign' => 4], [], []);

        $this->assertSame(3, $stub->getIdentifier());
    }

    /**
     * @covers ::getIdentifier
     */
    public function testGetIdentifierReturnsZeroIfIdentifierCouldNotBeDetermined()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct('pages', [], [], [], []);

        $this->assertSame(0, $stub->getIdentifier());
    }

    /**
     * @covers ::setParentRecord
     * @covers ::getParentRecord
     * @covers ::lockParentRecord
     * @covers ::isParentRecordLocked
     * @covers ::unlockParentRecord
     * @depends testSetParentRecordNotSetsParentRecordIfParentRecordIsLocked
     */
    public function testParentRecordCanBeSetAgainAfterParentRecordWasUnlocked()
    {
        $root = $this->getRecordStub([]);
        $root->__construct('pages', [], [], [], []);

        $content = $this->getRecordStub([]);
        $content->__construct('tt_content', [], [], [], []);

        $content->lockParentRecord();
        $this->assertTrue($content->isParentRecordLocked());
        $content->unlockParentRecord();

        $content->setParentRecord($root);

        $this->assertSame($root, $content->getParentRecord());
    }

    /**
     * @covers ::getBreadcrumb
     * @covers ::getRecordPath
     * @depends testSetParentRecordSetsParentRecord
     */
    public function testGetBreadcrumbReturnsSlashImplodedStringToRootRecord()
    {
        $root = $this->getRecordStub([]);
        $root->__construct('pages', ['uid' => 1], [], [], []);

        $content = $this->getRecordStub([]);
        $content->__construct('tt_content', ['uid' => 4], [], [], []);

        $content->setParentRecord($root);

        $this->assertSame('/ pages [1] / tt_content [4]', $content->getBreadcrumb());
    }

    /**
     * @covers ::isLocalRecordDisabled
     * @covers ::isRecordMarkedAsDisabledByProperties
     */
    public function testIsLocalRecordDisabledReturnsTrueIfDisableFieldIsTrue()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct(
            'pages',
            ['foo' => true],
            ['foo' => false],
            ['ctrl' => ['enablecolumns' => ['disabled' => 'foo']]],
            []
        );

        $this->assertTrue($stub->isLocalRecordDisabled());
    }

    /**
     * @covers ::isLocalRecordDisabled
     * @covers ::isRecordMarkedAsDisabledByProperties
     */
    public function testIsLocalRecordDisabledReturnsFalseIfDisableFieldIsNotTrue()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct(
            'pages',
            ['foo' => 0],
            ['foo' => 1],
            ['ctrl' => ['enablecolumns' => ['disabled' => 'foo']]],
            []
        );

        $this->assertFalse($stub->isLocalRecordDisabled());
    }

    /**
     * @covers ::isForeignRecordDisabled
     * @covers ::isRecordMarkedAsDisabledByProperties
     */
    public function testIsForeignRecordDisabledReturnsTrueIfDisableFieldIsTrue()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct(
            'pages',
            ['foo' => false],
            ['foo' => true],
            ['ctrl' => ['enablecolumns' => ['disabled' => 'foo']]],
            []
        );

        $this->assertTrue($stub->isForeignRecordDisabled());
    }

    /**
     * @covers ::isForeignRecordDisabled
     * @covers ::isRecordMarkedAsDisabledByProperties
     */
    public function testIsForeignRecordDisabledReturnsFalseIfDisableFieldIsNotTrue()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct(
            'pages',
            ['foo' => 1],
            ['foo' => 0],
            ['ctrl' => ['enablecolumns' => ['disabled' => 'foo']]],
            []
        );

        $this->assertFalse($stub->isForeignRecordDisabled());
    }

    /**
     * @covers ::isRecordMarkedAsDisabledByProperties
     * @depends testIsForeignRecordDisabledReturnsTrueIfDisableFieldIsTrue
     */
    public function testIsRecordMarkedAsDisabledByPropertiesDoesNotTriggerNoticeWhenDisableFieldIsNotSetInTca()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct(
            'pages',
            ['foo' => 1],
            ['foo' => 0],
            [],
            []
        );

        $this->assertFalse($stub->isForeignRecordDisabled());
    }

    /**
     * @covers ::isChanged
     * @covers ::getState
     * @depends testSysFileRecordsAreTreatedAsNormalRecordsIfIdentifierPropertiesDoNotDiffer
     */
    public function testIsChangedReturnsFalseForUnchangedState()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct('sys_file', ['identifier' => 'bar'], ['identifier' => 'bar'], [], []);
        $this->assertFalse($stub->isChanged());
    }

    /**
     * @covers ::isChanged
     * @covers ::getState
     * @depends testSysFileRecordsAreMovedIfIdentifiersDifferInsteadOfChanged
     * @depends testRecordIsValidIfTableNameIsFolderAndNameIsSet
     * @depends testRecordIsInvalidIfTableNameIsFolderAndNameIsNotSet
     * @depends testStateOfRecordIsDeletedIfOnlyForeignPropertiesAreSet
     */
    public function testIsChangedReturnsTrueForAnyOtherStateThanChanged()
    {
        // moved
        $stub = $this->getRecordStub([]);
        $stub->__construct('sys_file', ['identifier' => 'bar'], ['identifier' => 'baz'], [], []);
        $this->assertTrue($stub->isChanged());

        // changed
        $stub = $this->getRecordStub([]);
        $stub->__construct('folders', ['name' => 'bar'], ['name' => 'baz'], [], []);
        $this->assertTrue($stub->isChanged());

        // added
        $stub = $this->getRecordStub([]);
        $stub->__construct('folders', ['name' => 'bar'], [], [], []);
        $this->assertTrue($stub->isChanged());

        // deleted
        $stub = $this->getRecordStub([]);
        $stub->__construct('tt_content', [], ['uid' => 1], [], []);
        $this->assertTrue($stub->isChanged());
    }

    /**
     * @covers ::getRelatedRecords
     * @covers ::addRelatedRecord
     */
    public function testAddAndGetRelatedRecordsSetsAndReturnsRelatedRecords()
    {
        $root = $this->getRecordStub([]);
        $root->__construct('pages', ['uid' => 1], [], [], []);
        $sub = $this->getRecordStub([]);
        $sub->__construct('tt_content', ['uid' => 2], [], [], []);

        $root->addRelatedRecord($sub);

        $this->assertSame(
            [
                'tt_content' => [
                    2 => $sub,
                ],
            ],
            $root->getRelatedRecords()
        );
    }

    /**
     * @covers ::addRelatedRecord
     * @covers ::setParentRecord
     * @covers ::getParentRecord
     */
    public function testAddRelatedRecordsSetsParentRecord()
    {
        $root = $this->getRecordStub([]);
        $root->__construct('pages', ['uid' => 1], [], [], []);

        $sub = $this->getRecordStub([]);
        $sub->__construct('tt_content', ['uid' => 2], [], [], []);

        $root->addRelatedRecord($sub);

        $this->assertSame($root, $sub->getParentRecord());
    }

    /**
     * @covers ::addRelatedRecord
     * @depends testAddAndGetRelatedRecordsSetsAndReturnsRelatedRecords
     */
    public function testAddRelatedRecordDoesNotAddUnrelatedPageToPageRecord()
    {
        $root = $this->getRecordStub([]);
        $root->__construct('pages', ['uid' => 1], ['uid' => 1], [], []);

        $sub = $this->getRecordStub([]);
        $sub->__construct('pages', ['uid' => 200], [], [], []);

        $root->addRelatedRecord($sub);

        $this->assertSame([], $root->getRelatedRecords());
    }

    /**
     * @covers ::addRelatedRecord
     * @depends testAddAndGetRelatedRecordsSetsAndReturnsRelatedRecords
     */
    public function testAddRelatedRecordDoesAddLanguageParentPageToPageRecord()
    {
        $GLOBALS['TCA']['pages']['ctrl']['languageField'] = 'lang';
        $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'] = 'lang_parent';

        $root = $this->getRecordStub([]);
        $root->__construct('pages', ['uid' => 1, 'lang' => 1, 'lang_parent' => 200], ['uid' => 1], [], []);

        $sub = $this->getRecordStub([]);
        $sub->__construct('pages', ['uid' => 200], [], [], []);

        $root->addRelatedRecord($sub);

        $this->assertSame(['pages' => [200 => $sub]], $root->getRelatedRecords());
    }

    /**
     * @covers ::getStateRecursive
     * @depends testIsChangedReturnsTrueForAnyOtherStateThanChanged
     * @depends testIsChangedReturnsFalseForUnchangedState
     * @depends testAddAndGetRelatedRecordsSetsAndReturnsRelatedRecords
     */
    public function testGetStateRecursiveReturnsRootStateIfRootIsNotUnchanged()
    {
        $root = $this->getRecordStub([]);
        $root->__construct('pages', ['uid' => 1, 'foo' => 1], ['uid' => 1, 'foo' => 2], [], []);
        $sub = $this->getRecordStub([]);
        $sub->__construct('tt_content', [], ['uid' => 1], [], []);

        $root->addRelatedRecord($sub);

        $this->assertSame(Record::RECORD_STATE_CHANGED, $root->getStateRecursive());
    }

    /**
     * @covers ::getStateRecursive
     * @covers ::isChangedRecursive
     * @depends testIsChangedReturnsTrueForAnyOtherStateThanChanged
     * @depends testIsChangedReturnsFalseForUnchangedState
     * @depends testAddAndGetRelatedRecordsSetsAndReturnsRelatedRecords
     */
    public function testGetStateRecursiveReturnsRelatedRecordsStateIfRootIsUnchanged()
    {
        $root = $this->getRecordStub([]);
        $root->__construct('pages', ['uid' => 1], ['uid' => 1], [], []);
        $sub = $this->getRecordStub([]);
        $sub->__construct('tt_content', ['uid' => 1], [], [], []);

        $root->addRelatedRecord($sub);

        $this->assertSame(Record::RECORD_STATE_CHANGED, $root->getStateRecursive());
    }

    /**
     * @covers ::getStateRecursive
     * @covers ::isChangedRecursive
     * @depends testIsChangedReturnsTrueForAnyOtherStateThanChanged
     * @depends testIsChangedReturnsFalseForUnchangedState
     * @depends testAddAndGetRelatedRecordsSetsAndReturnsRelatedRecords
     */
    public function testGetStateRecursiveChecksEachRecordOnce()
    {
        $root = $this->getRecordStub([]);
        $root->__construct('pages', ['uid' => 1], ['uid' => 1], [], []);

        /** @var Record|MockObject $stub1 */
        $stub1 = $this->getMockBuilder(Record::class)
                      ->setMethods(['getIgnoreFields', 'isParentDisabled', 'isChanged'])
                      ->disableOriginalConstructor()
                      ->getMock();
        $stub1->method('getIgnoreFields')->will($this->returnValue([]));
        $stub1->method('isParentDisabled')->will($this->returnValue(false));
        $stub1->__construct('tt_content', ['uid' => 1], ['uid' => 1], [], []);

        $stub1->expects($this->once())->method('isChanged');

        /** @var Record|MockObject $stub2 */
        $stub2 = $this->getMockBuilder(Record::class)
                      ->setMethods(['getIgnoreFields', 'isParentDisabled', 'isChanged'])
                      ->disableOriginalConstructor()
                      ->getMock();
        $stub2->method('getIgnoreFields')->will($this->returnValue([]));
        $stub2->method('isParentDisabled')->will($this->returnValue(false));
        $stub2->__construct('tt_content', ['uid' => 2], ['uid' => 2], [], []);

        $stub2->expects($this->once())->method('isChanged');

        $stub2->addRelatedRecord($stub1);

        $root->addRelatedRecord($stub1);
        $root->addRelatedRecord($stub2);

        $root->getStateRecursive();
    }

    /**
     * @covers ::getStateRecursive
     * @depends testIsChangedReturnsTrueForAnyOtherStateThanChanged
     * @depends testIsChangedReturnsFalseForUnchangedState
     * @depends testAddAndGetRelatedRecordsSetsAndReturnsRelatedRecords
     * @depends testAddRelatedRecordDoesNotAddUnrelatedPageToPageRecord
     */
    public function testGetStateRecursiveIgnoresRelatedPageRecords()
    {
        $root = $this->getRecordStub([]);
        $root->__construct('tt_content', ['uid' => 1], ['uid' => 1], [], []);

        $sub = $this->getRecordStub([]);
        $sub->__construct('pages', ['uid' => 1], [], [], []);

        $root->addRelatedRecord($sub);

        $this->assertSame(Record::RECORD_STATE_UNCHANGED, $root->getStateRecursive());
    }

    /**
     * @covers ::getAdditionalProperties
     */
    public function testGetAdditionalPropertiesReturnsAdditionalProperties()
    {
        $record = $this->getRecordStub([]);
        $expectedProperties = ['foo' => 'bar'];
        $record->__construct('pages', [], [], [], $expectedProperties);
        $this->assertSame($expectedProperties, $record->getAdditionalProperties());
    }

    /**
     * @covers ::getAdditionalProperty
     */
    public function testGetAdditionalPropertyReturnsDesiredAdditionalProperties()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', [], [], [], ['foo' => 'bar']);
        $this->assertSame('bar', $record->getAdditionalProperty('foo'));
    }

    /**
     * @covers ::getAdditionalProperty
     */
    public function testAdditionalPropertyReturnsNullIfPropertyIsNotSet()
    {
        $record = $this->getRecordStub([]);
        $this->assertNull($record->getAdditionalProperty('foo'));
    }

    /**
     * @covers ::hasAdditionalProperty
     */
    public function testHasAdditionalPropertyReturnsTrueIfAdditionalPropertyIsSet()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', [], [], [], ['foo' => 'bar']);
        $this->assertTrue($record->hasAdditionalProperty('foo'));
    }

    /**
     * @covers ::hasAdditionalProperty
     */
    public function testHasAdditionalPropertyReturnsFalseIfPropertyIsNotSet()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', [], [], [], []);
        $this->assertFalse($record->hasAdditionalProperty('foo'));
    }

    /**
     * @covers ::setAdditionalProperties
     * @depends testGetAdditionalPropertiesReturnsAdditionalProperties
     */
    public function testSetAdditionalPropertiesSetsThemAndAllowsChaining()
    {
        $record = $this->getRecordStub([]);
        $this->assertSame($record, $record->setAdditionalProperties(['foo']));
        $this->assertSame(['foo'], $record->getAdditionalProperties());
    }

    /**
     * @covers ::addAdditionalProperty
     * @depends testGetAdditionalPropertyReturnsDesiredAdditionalProperties
     */
    public function testAddAdditionalPropertySetsNewProperty()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', [], [], [], []);
        $record->addAdditionalProperty('foo', 'bar');
        $this->assertSame('bar', $record->getAdditionalProperty('foo'));
    }

    /**
     * @covers ::addAdditionalProperty
     * @depends testGetAdditionalPropertyReturnsDesiredAdditionalProperties
     */
    public function testAddAdditionalPropertyOverwritesExistingProperty()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', [], [], [], ['foo' => 'baz']);
        $this->assertSame('baz', $record->getAdditionalProperty('foo'));
        $record->addAdditionalProperty('foo', 'bar');
        $this->assertSame('bar', $record->getAdditionalProperty('foo'));
    }

    /**
     * @covers ::addRelatedRecordRaw
     */
    public function testAddRelatedRecordRawAddsRecordToDefinedTableIndex()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', [], [], [], ['foo' => 'baz']);

        $related = $this->getRecordStub([]);
        $related->__construct('tt_content', [], [], [], ['foo' => 'baz']);

        $record->addRelatedRecordRaw($related, 'bazinga');

        $this->assertSame(
            [
                'bazinga' => [
                    0 => $related,
                ],
            ],
            $record->getRelatedRecords()
        );
    }

    /**
     * @covers ::addRelatedRecordRaw
     */
    public function testAddRelatedRecordRawCanAddRecordTwice()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', [], [], [], ['foo' => 'baz']);

        $related = $this->getRecordStub([]);
        $related->__construct('tt_content', [], [], [], ['foo' => 'baz']);

        $record->addRelatedRecordRaw($related, 'bazinga');
        $record->addRelatedRecordRaw($related, 'bazinga');

        $this->assertSame(
            [
                'bazinga' => [
                    0 => $related,
                    1 => $related,
                ],
            ],
            $record->getRelatedRecords()
        );
    }

    /**
     * @covers ::addRelatedRecordRaw
     * @depends testSetParentRecordNotSetsParentRecordIfParentRecordIsLocked
     */
    public function testAddRelatedRecordRawIgnoresParentRecordLockedAndRecordUid()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', [], [], [], []);

        $related = $this->getRecordStub([]);
        $related->__construct('tt_content', ['uid' => 3], ['uid' => 3], [], []);

        $related->lockParentRecord();

        $record->addRelatedRecordRaw($related, 'bazinga');

        $this->assertSame(
            [
                'bazinga' => [
                    0 => $related,
                ],
            ],
            $record->getRelatedRecords()
        );
    }

    /**
     * @covers ::addRelatedRecords
     * @depends testSetParentRecordNotSetsParentRecordIfParentRecordIsLocked
     */
    public function testAddRelatedRecordsAddsArrayOfRelatedRecordsAndAllowsChaining()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', [], [], [], []);

        $related1 = $this->getRecordStub([]);
        $related1->__construct('tt_content', ['uid' => 1], ['uid' => 1], [], []);

        $related2 = $this->getRecordStub([]);
        $related2->__construct('tt_content', ['uid' => 2], ['uid' => 2], [], []);

        $this->assertSame(
            $record,
            $record->addRelatedRecords([$related1, $related2]),
            '[!!!] \In2code\In2publishCore\Domain\Model\Record::addRelatedRecords must allow chaining'
        );

        $this->assertSame(
            [
                'tt_content' => [
                    1 => $related1,
                    2 => $related2,
                ],
            ],
            $record->getRelatedRecords()
        );
    }

    /**
     * @covers ::removeRelatedRecord
     * @depends testAddRelatedRecordsAddsArrayOfRelatedRecordsAndAllowsChaining
     */
    public function testRemoveRelatedRecordRemovesPreviouslyAddedRecord()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', [], [], [], []);

        $related1 = $this->getRecordStub([]);
        $related1->__construct('tt_content', ['uid' => 1], ['uid' => 1], [], []);

        $related2 = $this->getRecordStub([]);
        $related2->__construct('tt_content', ['uid' => 2], ['uid' => 2], [], []);

        $record->addRelatedRecords([$related1, $related2]);

        $record->removeRelatedRecord($related1);

        $this->assertSame(
            [
                'tt_content' => [
                    2 => $related2,
                ],
            ],
            $record->getRelatedRecords()
        );
    }

    /**
     * @return array
     */
    public function propertiesDataProvider(): array
    {
        // @formatter:off
        // @codingStandardsIgnoreStart
        return [
            'none' => [[], [], null],
            'only_local' => [['foo' => 'bar'], [], 'bar'],
            'only_foreign' => [[], ['foo' => 'baz'], 'baz'],
            'both_same' => [['foo' => 'boo'], ['foo' => 'boo'], 'boo'],
            'different_strings' => [['foo' => 'faz'], ['foo' => 'baz'], 'faz,baz'],
            'different_integers' => [['foo' => 2], ['foo' => 4], 2],
            'only_local_array' => [['foo' => ['baz' => 2]], [], ['baz' => 2]],
            'only_foreign_array' => [[], ['foo' => ['baz' => 2]], ['baz' => 2]],
            'both_same_array' => [['foo' => ['baz' => 2]], ['foo' => ['baz' => 2]], ['baz' => 2]],
            'different_array' => [
                ['foo' => ['bar' => 'foo']],
                ['foo' => ['baz' => 'faz']],
                ['bar' => 'foo', 'baz' => 'faz'],
            ],
            'local_array_foreign_string' => [['foo' => ['bar' => 'foo']], ['foo' => 'faz'], ['bar' => 'foo', 'faz']],
            'foreign_array_local_string' => [['foo' => 'faz'], ['foo' => ['bar' => 'foo']], ['faz', 'bar' => 'foo']],
            'only_local_list' => [['foo' => '2,3'], [], '2,3'],
            'only_foreign_list' => [[], ['foo' => '2,3'], '2,3'],
            'both_list' => [['foo' => '2,3'], ['foo' => '2,3'], '2,3'],
            'different_list' => [['foo' => '2,3'], ['foo' => '4,5'], '2,3,4,5'],
            'local_zero' => [['foo' => '0'], ['foo' => '1'], '1'],
            'foreign_zero' => [['foo' => '2'], ['foo' => '0'], '2'],
            'local_true' => [['foo' => true], [], true],
            'local_false' => [['foo' => false], [], false],
            'foreign_true' => [[], ['foo' => true], true],
            'foreign_false' => [[], ['foo' => false], false],
            'both_true' => [['foo' => true], ['foo' => true], true],
            'both_false' => [['foo' => false], ['foo' => false], false],
            'different_true' => [['foo' => true], ['foo' => false], true],
            'different_false' => [['foo' => false], ['foo' => true], true],
        ];
        // @codingStandardsIgnoreEnd
        // @formatter:on
    }

    /**
     * @covers ::getMergedProperty
     * @dataProvider propertiesDataProvider
     *
     * @param array $localProperties
     * @param array $foreignProperties
     * @param mixed $expectedProperty
     */
    public function testGetMergedPropertyReturnsExpectedValue(
        array $localProperties,
        array $foreignProperties,
        $expectedProperty
    ) {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', $localProperties, $foreignProperties, [], []);
        $this->assertSame($expectedProperty, $record->getMergedProperty('foo'));
    }

    /**
     * @covers ::sortRelatedRecords
     * @depends testAddRelatedRecordsAddsArrayOfRelatedRecordsAndAllowsChaining
     */
    public function testSortRelatedRecordsSortsRecordsOfTableWithGivenFunctionAndKeepsIndexes()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', [], [], [], []);

        $related1 = $this->getRecordStub([]);
        $related1->__construct('tt_content', ['uid' => 1], ['uid' => 1], [], []);

        $related2 = $this->getRecordStub([]);
        $related2->__construct('tt_content', ['uid' => 2], ['uid' => 2], [], []);

        $record->addRelatedRecords([$related1, $related2]);

        $record->sortRelatedRecords(
            'tt_content',
            function ($rel1, $rel2) {
                /**
                 * @var Record $rel1
                 * @var Record $rel2
                 */
                // sort "reverse" order
                return -strcmp((string)$rel1->getIdentifier(), (string)$rel2->getIdentifier());
            }
        );

        $this->assertSame(
            [
                'tt_content' => [
                    2 => $related2,
                    1 => $related1,
                ],
            ],
            $record->getRelatedRecords()
        );
    }

    /**
     * @covers ::getChangedRelatedRecordsFlat
     * @covers ::addChangedRelatedRecordsRecursive
     * @depends testAddRelatedRecordsAddsArrayOfRelatedRecordsAndAllowsChaining
     */
    public function testGetChangedRelatedRecordsFlatReturnsFlatArrayOfChangedRelatedRecords()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', [], [], [], []);

        $related1 = $this->getRecordStub([]);
        $related1->__construct('tt_content', ['uid' => 1, 'foo' => 'bar'], ['uid' => 1], [], []);

        $related2 = $this->getRecordStub([]);
        $related2->__construct('tt_content', [], ['uid' => 2], [], []);

        $related3 = $this->getRecordStub([]);
        $related3->__construct('tt_content', ['uid' => 6], ['uid' => 6], [], []);

        $record->addRelatedRecords([$related1, $related2, $related3]);

        $this->assertSame([$related1, $related2], $record->getChangedRelatedRecordsFlat());
    }

    /**
     * @covers ::getChangedRelatedRecordsFlat
     * @covers ::addChangedRelatedRecordsRecursive
     * @depends testGetChangedRelatedRecordsFlatReturnsFlatArrayOfChangedRelatedRecords
     */
    public function testGetChangedRelatedRecordsFlatReturnsFlatArrayOfChangedRelatedRecordsWithoutDuplicates()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', [], [], [], []);

        $related1 = $this->getRecordStub([]);
        $related1->__construct('tt_content', ['uid' => 1, 'foo' => 'bar'], ['uid' => 1], [], []);

        $related2 = $this->getRecordStub([]);
        $related2->__construct('tt_content', [], ['uid' => 2], [], []);

        $related3 = $this->getRecordStub([]);
        $related3->__construct('tt_content', ['uid' => 6], ['uid' => 6], [], []);
        $related3->addRelatedRecord($related1);

        $record->addRelatedRecords([$related1, $related2, $related3]);

        $this->assertSame([$related1, $related2], $record->getChangedRelatedRecordsFlat());
    }

    /**
     * @covers ::getChangedRelatedRecordsFlat
     */
    public function testGetChangedRelatedRecordsAddsRootRecordIfChanged()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', [], ['uid' => 2], [], []);

        $this->assertSame([$record], $record->getChangedRelatedRecordsFlat());
    }

    /**
     * @covers ::isLocalPreviewAvailable
     */
    public function testIsLocalPreviewAvailableReturnsTrueIfPageCanBeRendered()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', ['uid' => 3, 'doktype' => 2], ['uid' => 3, 'doktype' => 223], [], []);
        $this->assertTrue($record->isLocalPreviewAvailable());
    }

    /**
     * @covers ::isLocalPreviewAvailable
     */
    public function testIsLocalPreviewAvailableReturnsFalseForHighDoktypes()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', ['uid' => 3, 'doktype' => 201], ['uid' => 3, 'doktype' => 201], [], []);
        $this->assertFalse($record->isLocalPreviewAvailable());
    }

    /**
     * @covers ::isLocalPreviewAvailable
     */
    public function testIsLocalPreviewAvailableReturnsFalseForOtherTablesThanPages()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('bar', ['uid' => 3, 'doktype' => 2], ['uid' => 3, 'doktype' => 2], [], []);
        $this->assertFalse($record->isLocalPreviewAvailable());
    }

    /**
     * @covers ::isLocalPreviewAvailable
     */
    public function testIsLocalPreviewAvailableReturnsFalseForRootPage()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', ['uid' => 0, 'doktype' => 2], ['uid' => 0, 'doktype' => 2], [], []);
        $this->assertFalse($record->isLocalPreviewAvailable());
    }

    /**
     * @covers ::isForeignPreviewAvailable
     */
    public function testIsForeignPreviewAvailableReturnsTrueIfPageCanBeRendered()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', ['uid' => 3, 'doktype' => 211], ['uid' => 3, 'doktype' => 2], [], []);
        $this->assertTrue($record->isForeignPreviewAvailable());
    }

    /**
     * @covers ::isForeignPreviewAvailable
     */
    public function testIsForeignPreviewAvailableReturnsFalseForHighDoktypes()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', ['uid' => 3, 'doktype' => 2], ['uid' => 3, 'doktype' => 222], [], []);
        $this->assertFalse($record->isForeignPreviewAvailable());
    }

    /**
     * @covers ::isForeignPreviewAvailable
     */
    public function testIsForeignPreviewAvailableReturnsFalseForOtherTablesThanPages()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('foo', ['uid' => 3, 'doktype' => 2], ['uid' => 3, 'doktype' => 100], [], []);
        $this->assertFalse($record->isForeignPreviewAvailable());
    }

    /**
     * @covers ::isForeignPreviewAvailable
     */
    public function testIsForeignPreviewAvailableReturnsFalseForRootPage()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', ['uid' => 0, 'doktype' => 2], ['uid' => 0, 'doktype' => 100], [], []);
        $this->assertFalse($record->isForeignPreviewAvailable());
    }

    /**
     * @covers ::setForeignProperties
     * @covers ::foreignRecordExists
     * @depends testStateOfRecordIsAddedIfOnlyLocalPropertiesAreSet
     * @depends testStateOfRecordIsDeletedIfOnlyForeignPropertiesAreSet
     */
    public function testRuntimeCacheIsResetIfForeignPropertiesChange()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('foo', ['uid' => 2], ['uid' => 100], [], []);
        $this->assertTrue($record->foreignRecordExists());
        $record->setForeignProperties([]);
        $this->assertFalse($record->foreignRecordExists());
    }

    /**
     * @covers ::setLocalProperties
     * @covers ::localRecordExists
     * @depends testStateOfRecordIsAddedIfOnlyLocalPropertiesAreSet
     * @depends testStateOfRecordIsDeletedIfOnlyForeignPropertiesAreSet
     */
    public function testRuntimeCacheIsResetIfLocalPropertiesChange()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('foo', ['uid' => 2], ['uid' => 100], [], []);
        $this->assertTrue($record->localRecordExists());
        $record->setLocalProperties([]);
        $this->assertFalse($record->localRecordExists());
    }

    /**
     * @covers ::setDirtyProperties
     */
    public function testSetDirtyPropertiesResetsOldDirtyProperties()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('foo', ['uid' => 100, 'foo' => 'baz'], ['uid' => 200, 'bar' => 'bem'], [], []);
        $record->setDirtyProperties();
        $this->assertSame(['uid', 'foo', 'bar'], $record->getDirtyProperties());
        $record->setForeignProperties(['uid' => 200, 'foo' => 'baz', 'bar' => 'bem']);
        $record->setDirtyProperties();
        $this->assertSame(['uid', 'bar'], $record->getDirtyProperties());
    }

    /**
     * @covers ::getRelatedRecordByTableAndProperty
     * @depends testAddRelatedRecordsAddsArrayOfRelatedRecordsAndAllowsChaining
     */
    public function testGetRelatedRecordByPropertyReturnsEmptyArrayForNonExistingRecord()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', [], [], [], []);

        $related1 = $this->getRecordStub([]);
        $related1->__construct('tt_content', ['uid' => 1, 'foo' => 'bar'], ['uid' => 1], [], []);

        $related2 = $this->getRecordStub([]);
        $related2->__construct('sys_file', [], ['uid' => 2], [], []);

        $record->addRelatedRecords([$related1, $related2]);

        $this->assertSame([], $record->getRelatedRecordByTableAndProperty('tt_content', 'boo', 'foo'));
    }

    /**
     * @covers ::getRelatedRecordByTableAndProperty
     */
    public function testGetRelatedRecordByPropertyReturnsEmptyArrayForNonExistingTable()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', [], [], [], []);

        $this->assertSame([], $record->getRelatedRecordByTableAndProperty('tt_content', 'boo', 'foo'));
    }

    /**
     * @covers ::getRelatedRecordByTableAndProperty
     * @depends testAddRelatedRecordsAddsArrayOfRelatedRecordsAndAllowsChaining
     */
    public function testGetRelatedRecordByPropertyReturnsExpectedPropertyInArray()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', [], [], [], []);

        $related1 = $this->getRecordStub([]);
        $related1->__construct('tt_content', ['uid' => 1, 'foo' => 'bar'], ['uid' => 1], [], []);

        $related2 = $this->getRecordStub([]);
        $related2->__construct('sys_file', [], ['uid' => 2], [], []);

        $record->addRelatedRecords([$related1, $related2]);

        $this->assertSame(
            [$related1->getIdentifier() => $related1],
            $record->getRelatedRecordByTableAndProperty('tt_content', 'foo', 'bar')
        );
    }

    /**
     * @covers ::setPropertiesBySideIdentifier
     * @depends testSetLocalPropertiesSetsLocalProperties
     * @depends testGetLocalPropertiesReturnsAllLocalProperties
     */
    public function testSetPropertiesBySideSetsPropertiesForLocalSide()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', [], [], [], []);

        $properties = ['foo' => 'bar'];
        $record->setPropertiesBySideIdentifier('local', $properties);

        $this->assertSame($properties, $record->getLocalProperties());
    }

    /**
     * @covers ::setPropertiesBySideIdentifier
     * @depends testSetForeignPropertiesSetsForeignProperties
     * @depends testGetForeignPropertiesReturnsAllForeignProperties
     */
    public function testSetPropertiesBySideSetsPropertiesForForeignSide()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', [], [], [], []);

        $properties = ['foo' => 'bar'];
        $record->setPropertiesBySideIdentifier('local', $properties);

        $this->assertSame($properties, $record->getLocalProperties());
    }

    /**
     * @covers ::setPropertiesBySideIdentifier
     * @depends testSetForeignPropertiesSetsForeignProperties
     * @depends testGetForeignPropertiesReturnsAllForeignProperties
     */
    public function testSetPropertiesBySideAllowsChaining()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct('pages', [], [], [], []);

        $this->assertSame(
            $stub,
            $stub->setPropertiesBySideIdentifier('local', []),
            '[!!!] \In2code\In2publishCore\Domain\Model\Record::setPropertiesBySideIdentifier must allow chaining'
        );
    }

    /**
     * @covers ::setPropertiesBySideIdentifier
     * @depends testSetForeignPropertiesSetsForeignProperties
     * @depends testGetForeignPropertiesReturnsAllForeignProperties
     */
    public function testSetPropertiesBySideThrowsExceptionForUndefinedSide()
    {

        $stub = $this->getRecordStub([]);
        $stub->__construct('pages', [], [], [], []);

        $this->expectException(LogicException::class);
        $this->expectExceptionCode(1475857626);
        $this->expectExceptionMessage('Can not set properties for undefined side "foo"');

        $stub->setPropertiesBySideIdentifier('foo', []);
    }

    /**
     * @covers ::getPropertiesBySideIdentifier
     * @depends testGetLocalPropertiesReturnsAllLocalProperties
     */
    public function getPropertiesBySideIdentifierReturnsLocalPropertiesForLocalSide()
    {
        $record = $this->getRecordStub([]);

        $properties = ['foo' => 'bar'];
        $record->__construct('pages', $properties, [], [], []);

        $this->assertSame($properties, $record->getPropertiesBySideIdentifier('local'));
    }

    /**
     * @covers ::getPropertiesBySideIdentifier
     * @depends testGetForeignPropertiesReturnsAllForeignProperties
     */
    public function getPropertiesBySideIdentifierReturnsForeignPropertiesForForeignSide()
    {
        $record = $this->getRecordStub([]);

        $properties = ['foo' => 'bar'];
        $record->__construct('pages', [], $properties, [], []);

        $this->assertSame($properties, $record->getPropertiesBySideIdentifier('foreign'));
    }

    /**
     * @covers ::getPropertiesBySideIdentifier
     */
    public function getPropertiesBySideIdentifierThrowsExceptionIfSideIsUndefined()
    {
        $stub = $this->getRecordStub([]);
        $stub->__construct('pages', [], [], [], []);

        $this->expectException(LogicException::class);
        $this->expectExceptionCode(1475858502);
        $this->expectExceptionMessage('Can not get Properties from undefined side "foo"');

        $stub->getPropertiesBySideIdentifier('foo');
    }

    /**
     * @covers ::getPropertyBySideIdentifier
     * @depends testGetLocalPropertyReturnsLocalProperty
     */
    public function testGetPropertyBySideIdentifierReturnsLocalProperty()
    {
        $record = $this->getRecordStub([]);

        $record->__construct('pages', ['foo' => 'bar'], [], [], []);

        $this->assertSame('bar', $record->getPropertyBySideIdentifier('local', 'foo'));
    }

    /**
     * @covers ::getPropertyBySideIdentifier
     * @depends testGetForeignPropertyReturnsForeignProperty
     */
    public function testGetPropertyBySideIdentifierReturnsForeignProperty()
    {
        $record = $this->getRecordStub([]);

        $record->__construct('pages', [], ['foo' => 'bar'], [], []);

        $this->assertSame('bar', $record->getPropertyBySideIdentifier('foreign', 'foo'));
    }

    /**
     * @covers ::getPropertyBySideIdentifier
     */
    public function testGetPropertyBySideIdentifierThrowsExceptionForUndefinedSide()
    {
        $record = $this->getRecordStub([]);
        $record->__construct('pages', [], [], [], []);

        $this->expectException(LogicException::class);
        $this->expectExceptionCode(1475858834);
        $this->expectExceptionMessage('Can not get property "bar" from undefined side "foo"');

        $this->assertSame('bar', $record->getPropertyBySideIdentifier('foo', 'bar'));
    }
}
