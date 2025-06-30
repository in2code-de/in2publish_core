<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\RecordTree;

use In2code\In2publishCore\Component\Core\RecordTree\RecordTreeBuildRequest;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(RecordTreeBuildRequest::class, '__construct')]
#[CoversMethod(RecordTreeBuildRequest::class, 'getTable')]
#[CoversMethod(RecordTreeBuildRequest::class, 'getId')]
#[CoversMethod(RecordTreeBuildRequest::class, 'withId')]
#[CoversMethod(RecordTreeBuildRequest::class, 'getPageRecursionLimit')]
#[CoversMethod(RecordTreeBuildRequest::class, 'getDependencyRecursionLimit')]
class RecordTreeBuildRequestTest extends UnitTestCase
{
    public function testConstructor(): void
    {
        $recordTreeBuildRequest = new RecordTreeBuildRequest('table_foo', 1, 3, 5);
        $this->assertSame('table_foo', $recordTreeBuildRequest->getTable());
        $this->assertSame(1, $recordTreeBuildRequest->getId());
        $this->assertSame(3, $recordTreeBuildRequest->getPageRecursionLimit());
        $this->assertSame(5, $recordTreeBuildRequest->getDependencyRecursionLimit());
    }

    public function testWithId(): void
    {
        $recordTreeBuildRequest = new RecordTreeBuildRequest('table_foo', 1, 3, 5);
        $recordTreeBuildRequestWithId = $recordTreeBuildRequest->withId(2);
        $this->assertSame(2, $recordTreeBuildRequestWithId->getId());
    }
}
