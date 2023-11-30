<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Publisher;

use In2code\In2publishCore\Component\Core\Publisher\DatabaseRecordPublisher;
use In2code\In2publishCore\Component\Core\Publisher\Exception\PublisherOverflowException;
use In2code\In2publishCore\Component\Core\Publisher\FileRecordPublisher;
use In2code\In2publishCore\Component\Core\Publisher\FinishablePublisher;
use In2code\In2publishCore\Component\Core\Publisher\Publisher;
use In2code\In2publishCore\Component\Core\Publisher\PublisherCollection;
use In2code\In2publishCore\Component\Core\Publisher\ReversiblePublisher;
use In2code\In2publishCore\Component\Core\Publisher\TransactionalPublisher;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Tests\UnitTestCase;
use ReflectionProperty;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Publisher\PublisherCollection
 */
class PublisherCollectionTest extends UnitTestCase
{
    /**
     * @covers ::addPublisher
     */
    public function testAddPublisherAddsPublisher()
    {
        $publisherCollection = new PublisherCollection();
        $reflectionPropertyPublishers = new ReflectionProperty($publisherCollection, 'publishers');
        $reflectionPropertyPublishers->setAccessible(true);

        $maxPublisherCount = 64;

        for ($i = 1; $i < $maxPublisherCount + 1; $i++) {
            $publisher = $this->createMock(Publisher::class);
            $publisherCollection->addPublisher($publisher);
            // only one assertion randomly chosen after adding 5 publishers
            if ($i === 5) {
                $this->assertCount($i, $reflectionPropertyPublishers->getValue($publisherCollection));
            } elseif ($i === $maxPublisherCount + 1) {
                $this->expectExceptionObject(new PublisherOverflowException($publisher, $maxPublisherCount));
            }
        }
    }

    /**
     * @covers ::addPublisher
     */
    public function testAddPublisherSortsPublishersCorrectly(): void
    {
        $publisherCollection = new PublisherCollection();
        $reflectionPropertyPublishers = new ReflectionProperty($publisherCollection, 'publishers');
        $reflectionPropertyPublishers->setAccessible(true);

        $standardPublisher = $this->createMock(Publisher::class);
        $transactionalPublisher = $this->createMock(DatabaseRecordPublisher::class);
        $finishablePublisher = $this->createMock(FinishablePublisher::class);

        $publisherCollection->addPublisher($standardPublisher);
        $publisherCollection->addPublisher($transactionalPublisher);
        $publisherCollection->addPublisher($finishablePublisher);

        $publishers = $reflectionPropertyPublishers->getValue($publisherCollection);

        $this->assertContains($standardPublisher, $publishers);
        $this->assertContains($transactionalPublisher, $publishers);
        $this->assertContains($finishablePublisher, $publishers);

        $arrayKeyStandardPublisher = array_search($standardPublisher, $publishers);
        $arrayKeyTransactionalPublisher = array_search($transactionalPublisher, $publishers);
        $arrayKeyFinishablePublisher = array_search($finishablePublisher, $publishers);

        // Prefer anything which is reversible and transactional
        $this->assertGreaterThan($arrayKeyStandardPublisher, $arrayKeyTransactionalPublisher);
        $this->assertGreaterThan($arrayKeyStandardPublisher, $arrayKeyFinishablePublisher);
        $this->assertGreaterThan($arrayKeyFinishablePublisher, $arrayKeyTransactionalPublisher);
    }

    /**
     * @covers ::publish
     */
    public function testPublishIsCalledByTheFirstPublisherThatCanPublishARecord()
    {
        $publisherCollection = new PublisherCollection();
        $reflectionPropertyPublishers = new ReflectionProperty($publisherCollection, 'publishers');
        $reflectionPropertyPublishers->setAccessible(true);

        $dbRecord = $this->createMock(DatabaseRecord::class);

        $transactionalPublisher = $this->createMock(DatabaseRecordPublisher::class);
        $transactionalPublisher->method('canPublish')->with($dbRecord)->willReturn(true);
        $transactionalPublisher->expects($this->once())->method('publish');

        $standardPublisher = $this->createMock(Publisher::class);
        $standardPublisher->method('canPublish')->with($dbRecord)->willReturn(true);
        $standardPublisher->expects($this->never())->method('publish');

        $finishablePublisher = $this->createMock(FileRecordPublisher::class);
        $finishablePublisher->method('canPublish')->with($dbRecord)->willReturn(false);
        $finishablePublisher->expects($this->never())->method('publish');

        $publisherCollection->addPublisher($transactionalPublisher);
        $publisherCollection->addPublisher($standardPublisher);
        $publisherCollection->addPublisher($finishablePublisher);

        $dbRecord = $this->createMock(DatabaseRecord::class);

        $publisherCollection->publish($dbRecord);
    }

    /**
     * @covers ::cancel
     */
    public function testCancelIsCalledByTransactionalPublishers()
    {
        $publisherCollection = new PublisherCollection();
        $transactionalPublisher = $this->getTransactionalPublisher();
        $publisherCollection->addPublisher($transactionalPublisher);

        $transactionalPublisher2 = $this->getTransactionalPublisher();
        $publisherCollection->addPublisher($transactionalPublisher2);

        $reversiblePublisher = $this->getReversiblePublisher();
        $publisherCollection->addPublisher($reversiblePublisher);

        $finishablePublisher = $this->getFinishablePublisher();
        $publisherCollection->addPublisher($finishablePublisher);

        $GLOBALS['number_of_calls_cancel'] = 0;
        try {
            $publisherCollection->cancel();
            $this->assertEquals(2, $GLOBALS['number_of_calls_cancel']);
        } finally {
            unset($GLOBALS['number_of_calls_cancel']);
        }
    }

    /**
     * @covers ::reverse
     */
    public function testReverseIsCalledByReversiblePublishers()
    {
        $publisherCollection = new PublisherCollection();
        $reversiblePublisher = $this->getReversiblePublisher();
        $publisherCollection->addPublisher($reversiblePublisher);

        $reversiblePublisher2 = $this->getReversiblePublisher();
        $publisherCollection->addPublisher($reversiblePublisher2);

        $transactionalPublisher = $this->getTransactionalPublisher();
        $publisherCollection->addPublisher($transactionalPublisher);

        $finishablePublisher = $this->getFinishablePublisher();
        $publisherCollection->addPublisher($finishablePublisher);

        $GLOBALS['number_of_calls_reverse'] = 0;
        try {
            $publisherCollection->reverse();
            $this->assertEquals(2, $GLOBALS['number_of_calls_reverse']);
        } finally {
            unset($GLOBALS['number_of_calls_reverse']);
        }
    }

    /**
     * @covers ::finish
     */
    public function testFinishIsCalledByFinishablePublishers()
    {
        $publisherCollection = new PublisherCollection();
        $finishablePublisher = $this->getFinishablePublisher();
        $publisherCollection->addPublisher($finishablePublisher);

        $finishablePublisher2 = $this->getFinishablePublisher();
        $publisherCollection->addPublisher($finishablePublisher2);

        $GLOBALS['number_of_calls_finish'] = 0;
        try {
            $publisherCollection->finish();
            $this->assertEquals(2, $GLOBALS['number_of_calls_finish']);
        } finally {
            unset($GLOBALS['number_of_calls_finish']);
        }
    }

    /**
     * @return Publisher|TransactionalPublisher
     */
    protected function getTransactionalPublisher()
    {
        return new class implements Publisher, TransactionalPublisher {
            public function start(): void
            {
            }

            // method should not be called in test
            public function finish(): void
            {
                $GLOBALS['number_of_calls_finish']++;
            }

            // method should not be called in test
            public function reverse(): void
            {
                $GLOBALS['number_of_calls_reverse']++;
            }

            public function cancel(): void
            {
                $GLOBALS['number_of_calls_cancel']++;
            }

            public function canPublish(Record $record): bool
            {
                return true;
            }

            public function publish(Record $record)
            {
            }
        };
    }

    protected function getReversiblePublisher()
    {
        return new class implements Publisher, ReversiblePublisher {
            public function start(): void
            {
            }

            // method should not be called in test
            public function finish(): void
            {
                $GLOBALS['number_of_calls_finish']++;
            }

            public function reverse(): void
            {
                $GLOBALS['number_of_calls_reverse']++;
            }

            // method should not be called in test
            public function cancel(): void
            {
                $GLOBALS['number_of_calls_cancel']++;
            }

            public function canPublish(Record $record): bool
            {
                return true;
            }

            public function publish(Record $record)
            {
            }
        };
    }

    protected function getFinishablePublisher()
    {
        return new class implements Publisher, FinishablePublisher {
            public function start(): void
            {
            }

            public function finish(): void
            {
                $GLOBALS['number_of_calls_finish']++;
            }

            // method should not be called in test
            public function reverse(): void
            {
                $GLOBALS['number_of_calls_reverse']++;
            }

            // method should not be called in test
            public function cancel(): void
            {
                $GLOBALS['number_of_calls_cancel']++;
            }

            public function canPublish(Record $record): bool
            {
                return true;
            }

            public function publish(Record $record)
            {
            }
        };
    }
}
