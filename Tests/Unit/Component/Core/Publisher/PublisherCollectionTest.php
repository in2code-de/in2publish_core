<?php

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Publisher;

use In2code\In2publishCore\Component\Core\Publisher\DatabaseRecordPublisher;
use In2code\In2publishCore\Component\Core\Publisher\Exception\PublisherOverflowException;
use In2code\In2publishCore\Component\Core\Publisher\FileRecordPublisher;
use In2code\In2publishCore\Component\Core\Publisher\FinishablePublisher;
use In2code\In2publishCore\Component\Core\Publisher\Publisher;
use In2code\In2publishCore\Component\Core\Publisher\PublisherCollection;
use In2code\In2publishCore\Component\Core\Publisher\ReversiblePublisher;
use In2code\In2publishCore\Component\Core\Publisher\TransactionalPublisher;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Tests\UnitTestCase;

/**
 * @coversDefaultClass PublisherCollection
 */
class PublisherCollectionTest extends UnitTestCase
{
    /**
     * @covers ::__addPublisher
     */
    public function testAddPublisherAddsPublisher()
    {
        $publisherCollection = new PublisherCollection();
        $reflectionPropertyPublishers = new \ReflectionProperty($publisherCollection, 'publishers');
        $reflectionPropertyPublishers->setAccessible(true);

        $maxPublisherCount = 64;

        for ($i = 1; $i < $maxPublisherCount + 1; $i++) {
            $publisher = $this->createMock(Publisher::class);
            $publisherCollection->addPublisher($publisher);
            // only one assertion randomly chosen after adding 5 publishers
            if ($i === 5) {
                $this->assertCount($i, $reflectionPropertyPublishers->getValue($publisherCollection));
            } else if ($i === $maxPublisherCount + 1) {
                $this->expectExceptionObject(new PublisherOverflowException($publisher, $maxPublisherCount));
            }
        }
    }

    /**
     * @covers ::__addPublisher
     */
    public function testAddPublisherSortsPublishersCorrectly()
    {
        $publisherCollection = new PublisherCollection();
        $reflectionPropertyPublishers = new \ReflectionProperty($publisherCollection, 'publishers');
        $reflectionPropertyPublishers->setAccessible(true);

        $standardPublisher = $this->createMock(Publisher::class);
        $transactionalPublisher = $this->createMock(DatabaseRecordPublisher::class);
        $finishablePublisher = $this->createMock(FileRecordPublisher::class);

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
}
