<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Record\Factory;

use In2code\In2publishCore\Component\Core\Record\Factory\DatabaseRecordFactory;
use In2code\In2publishCore\Component\Core\Record\Factory\DatabaseRecordFactoryFactory;
use In2code\In2publishCore\Component\Core\Record\Factory\Exception\MissingDatabaseRecordFactoryException;
use In2code\In2publishCore\Tests\UnitTestCase;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Record\Factory\DatabaseRecordFactoryFactory
 */
class DatabaseRecordFactoryFactoryTest extends UnitTestCase
{
    /**
     * @covers ::addFactory
     */
    public function testAddFactory(): void
    {
        $factory1 = $this->createMock(DatabaseRecordFactory::class);
        $factory1->expects($this->once())->method('getPriority')->willReturn(1);

        $factory2 = $this->createMock(DatabaseRecordFactory::class);
        $factory2->expects($this->once())->method('getPriority')->willReturn(2);

        $factory3 = $this->createMock(DatabaseRecordFactory::class);
        $factory3->expects($this->once())->method('getPriority')->willReturn(10);

        $factoryFactory = new DatabaseRecordFactoryFactory();
        $factoryFactory->addFactory($factory1);
        $factoryFactory->addFactory($factory2);
        $factoryFactory->addFactory($factory3);


        $reflectionProperty = new \ReflectionProperty(DatabaseRecordFactoryFactory::class, 'factories');
        $reflectionProperty->setAccessible(true);
        $factories = $reflectionProperty->getValue($factoryFactory);
        $expectedFactoryOrder = [
            10 => [$factory3],
            2 => [$factory2],
            1 => [$factory1],
        ];
        $this->assertSame($expectedFactoryOrder, $factories);
    }

    /**
     * @covers ::createFactoryForTable
     */
    public function testCreateFactoryForTableReturnsResponsibleFactory(): void
    {
        $table = 'table_foo';

        $factory1 = $this->createMock(DatabaseRecordFactory::class);
        $factory1->expects($this->once())->method('isResponsible')->with($table)->willReturn(false);

        $factory2 = $this->createMock(DatabaseRecordFactory::class);
        $factory2->expects($this->once())->method('isResponsible')->with($table)->willReturn(true);

        $factoryFactory = new DatabaseRecordFactoryFactory();
        $factoryFactory->addFactory($factory1);
        $factoryFactory->addFactory($factory2);


        $createdFactory = $factoryFactory->createFactoryForTable($table);
        $this->assertSame($factory2, $createdFactory);
    }

    /**
     * @covers ::createFactoryForTable
     */
    public function testCreateFactoryForTableThrowsExceptionIfNoFactoryIsFound(): void
    {
        $table = 'table_foo';

        $this->expectException(MissingDatabaseRecordFactoryException::class);
        $this->expectExceptionMessage('No factory found for table table_foo');

        $factory1 = $this->createMock(DatabaseRecordFactory::class);
        $factory1->expects($this->once())->method('isResponsible')->with($table)->willReturn(false);

        $factoryFactory = new DatabaseRecordFactoryFactory();
        $factoryFactory->addFactory($factory1);

        $factoryFactory->createFactoryForTable($table);
    }
}
