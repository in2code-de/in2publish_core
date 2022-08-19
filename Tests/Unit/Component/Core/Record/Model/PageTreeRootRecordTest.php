<?php

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Record\Model;

use In2code\In2publishCore\Component\Core\Record\Model\PageTreeRootRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Tests\UnitTestCase;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Record\Model\PageTreeRootRecord
 */
class PageTreeRootRecordTest extends UnitTestCase
{
    /**
     * @covers ::__construct
     */
    public function testAssumptionsAboutPageTreeRootRecord(): void
    {
        $record = new PageTreeRootRecord();

        $this->assertSame(0, $record->getId());
        $this->assertSame(0, $record->getPageId());
        $this->assertSame('pages', $record->getClassification());
        $this->assertSame([], $record->getLocalProps());
        $this->assertSame([], $record->getForeignProps());
        $this->assertSame([], $record->getChangedProps());
        $this->assertSame(0, $record->getLanguage());
        $this->assertSame([], $record->getParents());
        $this->assertSame(Record::S_UNCHANGED, $record->getState());
        $this->assertSame(Record::S_UNCHANGED, $record->getStateRecursive());
        $this->assertNull($record->getTranslationParent());
        $this->assertSame([], $record->getTranslations());
        $this->assertSame(0, $record->getTransOrigPointer());
    }
}
