<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Domain\Model;

use Exception;
use In2code\In2publishCore\Domain\Model\DatabaseRecord;
use In2code\In2publishCore\Tests\UnitTestCase;

use function bin2hex;
use function random_bytes;

/**
 * @coversDefaultClass \In2code\In2publishCore\Domain\Model\DatabaseRecord
 */
class DatabaseRecordTest extends UnitTestCase
{
    /**
     * @return void
     * @throws Exception
     * @covers ::__construct
     * @covers ::getTable
     * @covers ::getId
     * @covers ::getLocalFields
     * @covers ::getForeignFields
     */
    public function testDatabaseRecordCanBeInstantiated(): void
    {
        $identifier = random_int(1, 100);
        $table = bin2hex(random_bytes(8));

        $localFields = [
            'uid' => 1,
            'name' => 'foo',
        ];
        $foreignFields = [
            'uid' => 1,
            'name' => 'bar',
        ];

        $record = new DatabaseRecord($table, $identifier, $localFields, $foreignFields);
        $actualTable = $record->getClassification();
        $actualIdentifier = $record->getId();
        $actualLocalFields = $record->getLocalProps();
        $actualForeignFields = $record->getForeignProps();

        $this->assertSame($identifier, $actualIdentifier);
        $this->assertSame($table, $actualTable);
        $this->assertSame($localFields, $actualLocalFields);
        $this->assertSame($foreignFields, $actualForeignFields);
    }

    /**
     * @return void
     * @covers ::addChild
     * @covers ::addParent
     * @covers ::getChildren
     * @covers ::getParents
     */
    public function testRecordCanBeAddedAsChild(): void
    {
        $parent = new DatabaseRecord('foo', 1, [], []);
        $child = new DatabaseRecord('bar', 1, [], []);

        $parent->addChild($child);

        $this->assertSame(['bar' => [1 => $child]], $parent->getChildren());
        $this->assertSame([$parent], $child->getParents());
    }

    /**
     * @return void
     * @covers ::getField
     */
    public function testGetFieldReturnsValueWithFallback(): void
    {
        $record = new DatabaseRecord('foo', 1, ['bar' => 'beng'], ['boo' => 'bang']);

        $this->assertSame('beng', $record->getProp('bar'));
        $this->assertSame('bang', $record->getProp('boo'));
        $this->assertNull($record->getProp('foo'));
    }

    public function testGetPageIdReturnsIdOfPage(): void
    {
        $record = new DatabaseRecord('pages', 1, [], []);

        $actual = $record->getPageId();

        $this->assertSame(1, $actual);
    }

    public function testGetPageIdReturnsUidOfDefaultLanguageIfTranslated(): void
    {
        $GLOBALS['TCA']['pages']['ctrl']['languageField'] = 'language';
        $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'] = 'trans_parent';

        $record = new DatabaseRecord(
            'pages',
            1,
            [
                'language' => 1,
                'trans_parent' => 5
            ],
            [
                'language' => 1,
                'trans_parent' => 5
            ]
        );

        $actual = $record->getPageId();

        $this->assertSame(5, $actual);
    }

    public function testGetPageIdOnOtherTableThanPagesReturnsPid(): void
    {
        $record = new DatabaseRecord('foo', 1, ['pid' => 2], ['pid' => 2]);

        $actual = $record->getPageId();

        $this->assertSame(2, $actual);
    }
}
