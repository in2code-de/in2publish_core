<?php
namespace In2code\In2publishCore\Tests\Unit\Domain\Model;

/***************************************************************
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
 ***************************************************************/

use In2code\In2publishCore\Domain\Model\Record;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * @coversDefaultClass \In2code\In2publishCore\Domain\Model\Record
 */
class RecordTest extends UnitTestCase
{
    /**
     * @param mixed $getIgnoreFields
     * @param bool $isParentRecordDisabled
     * @return Record
     */
    protected function getRecordStub($getIgnoreFields, $isParentRecordDisabled = false)
    {
        $stub = $this->getMockBuilder(Record::class)
                     ->setMethods(['getIgnoreFields', 'isParentRecordDisabled'])
                     ->disableOriginalConstructor()
                     ->getMock();

        $stub->method('getIgnoreFields')->will($this->returnValue($getIgnoreFields));
        $stub->method('isParentRecordDisabled')->will($this->returnValue($isParentRecordDisabled));

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
     */
    public function testGetRelatedRecordsReturnsRelatedRecords()
    {
        $root = $this->getRecordStub([]);
        $root->__construct('pages', [], [], [], []);
        $sub = $this->getRecordStub([]);
        $sub->__construct('tt_content', [], [], [], []);

        $root->addRelatedRecord($sub);

        $this->assertSame(
            [
                'tt_content' => [
                    0 => $sub,
                ],
            ],
            $root->getRelatedRecords()
        );
    }

    /**
     * @covers ::getStateRecursive
     * @depends testIsChangedReturnsTrueForAnyOtherStateThanChanged
     * @depends testIsChangedReturnsFalseForUnchangedState
     * @depends testGetRelatedRecordsReturnsRelatedRecords
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
     * @depends testGetRelatedRecordsReturnsRelatedRecords
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
     * @depends testGetRelatedRecordsReturnsRelatedRecords
     */
    public function testGetStateRecursiveChecksEachRecordOnce()
    {
        $root = $this->getRecordStub([]);
        $root->__construct('pages', ['uid' => 1], ['uid' => 1], [], []);

        /** @var Record|\PHPUnit_Framework_MockObject_MockObject $stub1 */
        $stub1 = $this->getMockBuilder(Record::class)
                     ->setMethods(['getIgnoreFields', 'isParentRecordDisabled', 'isChanged'])
                     ->disableOriginalConstructor()
                     ->getMock();
        $stub1->method('getIgnoreFields')->will($this->returnValue([]));
        $stub1->method('isParentRecordDisabled')->will($this->returnValue(false));
        $stub1->__construct('tt_content', ['uid' => 1], ['uid' => 1], [], []);

        $stub1->expects($this->once())->method('isChanged');

        /** @var Record|\PHPUnit_Framework_MockObject_MockObject $stub2 */
        $stub2 = $this->getMockBuilder(Record::class)
                      ->setMethods(['getIgnoreFields', 'isParentRecordDisabled', 'isChanged'])
                      ->disableOriginalConstructor()
                      ->getMock();
        $stub2->method('getIgnoreFields')->will($this->returnValue([]));
        $stub2->method('isParentRecordDisabled')->will($this->returnValue(false));
        $stub2->__construct('tt_content', ['uid' => 2], ['uid' => 2], [], []);

        $stub2->expects($this->once())->method('isChanged');

        $stub2->addRelatedRecord($stub1);

        $root->addRelatedRecord($stub1);
        $root->addRelatedRecord($stub2);

        $root->getStateRecursive();
    }

    /**
     * @covers ::addRelatedRecord
     */
    public function testAddRelatedRecordDoesNotAddPageToPageRecord()
    {
        $root = $this->getRecordStub([]);
        $root->__construct('pages', ['uid' => 1], ['uid' => 1], [], []);

        $sub = $this->getRecordStub([]);
        $sub->__construct('pages', ['uid' => 1], [], [], []);

        $root->addRelatedRecord($sub);

        $this->assertSame([], $root->getRelatedRecords());
    }

    /**
     * @covers ::getStateRecursive
     * @depends testIsChangedReturnsTrueForAnyOtherStateThanChanged
     * @depends testIsChangedReturnsFalseForUnchangedState
     * @depends testGetRelatedRecordsReturnsRelatedRecords
     * @depends testAddRelatedRecordDoesNotAddPageToPageRecord
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
}
