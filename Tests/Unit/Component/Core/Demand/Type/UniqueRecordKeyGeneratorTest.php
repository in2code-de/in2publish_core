<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Demand\Type;

use In2code\In2publishCore\Component\Core\Demand\Type\UniqueRecordKeyGenerator;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(UniqueRecordKeyGenerator::class, 'createUniqueRecordKey')]
class UniqueRecordKeyGeneratorTest extends UnitTestCase
{
    public function testUniqueRecordKeyReturnsUniqueIdentifier(): void
    {
        $record1 = $this->createMock(DatabaseRecord::class);
        $record1->method('getClassification')->willReturn('table_');
        $record1->method('getId')->willReturn(41);

        $record2 = $this->createMock(DatabaseRecord::class);
        $record2->method('getClassification')->willReturn('table_4');
        $record2->method('getId')->willReturn(1);

        $object = new UniqueRecordKeyGeneratorClass();

        $record1Key = $object->createUniqueRecordKey($record1);
        $record2Key = $object->createUniqueRecordKey($record2);

        $this->assertNotSame($record1Key, $record2Key);
    }
}
