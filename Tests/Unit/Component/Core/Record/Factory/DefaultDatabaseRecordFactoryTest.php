<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Record\Factory;

use In2code\In2publishCore\Component\Core\Record\Factory\DefaultDatabaseRecordFactory;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Tests\UnitTestCase;
use ReflectionProperty;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Record\Factory\DefaultDatabaseRecordFactory
 */
class DefaultDatabaseRecordFactoryTest extends UnitTestCase
{
    /**
     * @covers ::getPriority
     * @covers ::isResponsible
     */
    public function testConstructor(): void
    {
        $databaseRecordFactoryFactory = new DefaultDatabaseRecordFactory();
        $this->assertInstanceOf(DefaultDatabaseRecordFactory::class, $databaseRecordFactoryFactory);
        $this->assertSame(0, $databaseRecordFactoryFactory->getPriority());
        $this->assertSame(true, $databaseRecordFactoryFactory->isResponsible('table_foo'));
        $this->assertSame(true, $databaseRecordFactoryFactory->isResponsible('table_bar'));
    }

    /**
     * @covers ::createDatabaseRecord
     */
    public function testCreateDatabaseRecordCreatesValidDatabaseRecord(): void
    {
        $databaseRecordFactoryFactory = new DefaultDatabaseRecordFactory();
        $databaseRecord = $databaseRecordFactoryFactory->createDatabaseRecord(
            'table_foo',
            1,
            ['foo' => 'bar'],
            ['bar' => 'foo'],
            [],
        );
        $reflectionProperty = new ReflectionProperty($databaseRecord, 'ignoredProps');
        $reflectionProperty->setAccessible(true);
        $ignoredProps = $reflectionProperty->getValue($databaseRecord);

        $this->assertInstanceOf(DatabaseRecord::class, $databaseRecord);
        $this->assertSame('table_foo', $databaseRecord->getClassification());
        $this->assertSame(1, $databaseRecord->getId());
        $this->assertSame(['foo' => 'bar'], $databaseRecord->getLocalProps());
        $this->assertSame(['bar' => 'foo'], $databaseRecord->getForeignProps());
        $this->assertSame([], $ignoredProps);
    }

}
