<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Record\Factory;

use In2code\In2publishCore\Component\Core\Record\Factory\TtContentDatabaseRecordFactory;
use In2code\In2publishCore\Component\Core\Record\Model\TtContentDatabaseRecord;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use ReflectionProperty;

#[CoversMethod(TtContentDatabaseRecordFactory::class, 'getPriority')]
#[CoversMethod(TtContentDatabaseRecordFactory::class, 'isResponsible')]
#[CoversMethod(TtContentDatabaseRecordFactory::class, 'createDatabaseRecord')]
class TfContentDatabaseRecordFactoryTest extends UnitTestCase
{
    public function testConstructor(): void
    {
        $databaseRecordFactoryFactory = new TtContentDatabaseRecordFactory();
        $this->assertInstanceOf(TtContentDatabaseRecordFactory::class, $databaseRecordFactoryFactory);
        $this->assertSame(100, $databaseRecordFactoryFactory->getPriority());
        $this->assertTrue($databaseRecordFactoryFactory->isResponsible('tt_content'));
        $this->assertFalse($databaseRecordFactoryFactory->isResponsible('table_foo'));
    }

    public function testCreateDatabaseRecordCreatesValidTtContentDatabaseRecord(): void
    {
        $databaseRecordFactoryFactory = new TtContentDatabaseRecordFactory();
        $databaseRecord = $databaseRecordFactoryFactory->createDatabaseRecord(
            'tt_content',
            42,
            ['foo' => 'bar'],
            ['bar' => 'foo'],
            [],
        );
        $reflectionProperty = new ReflectionProperty($databaseRecord, 'ignoredProps');
        $reflectionProperty->setAccessible(true);
        $ignoredProps = $reflectionProperty->getValue($databaseRecord);

        $this->assertInstanceOf(TtContentDatabaseRecord::class, $databaseRecord);
        $this->assertSame('tt_content', $databaseRecord->getClassification());
        $this->assertSame(42, $databaseRecord->getId());
        $this->assertSame(['foo' => 'bar'], $databaseRecord->getLocalProps());
        $this->assertSame(['bar' => 'foo'], $databaseRecord->getForeignProps());
        $this->assertSame([], $ignoredProps);
    }
}
