<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher;

use In2code\In2publishCore\Component\Core\Publisher\Exception\PublisherNotFoundException;
use In2code\In2publishCore\Component\Core\Publisher\Exception\PublisherOverflowException;
use In2code\In2publishCore\Component\Core\Record\Model\Record;

use function bindec;
use function count;
use function decbin;
use function krsort;
use function str_pad;

use const STR_PAD_LEFT;

class PublisherCollection implements ReversiblePublisher, TransactionalPublisher
{
    private const PUBLISHER_PADDING = 6;
    /**
     * @var array<Publisher>
     */
    protected array $publishers = [];

    public function addPublisher(Publisher $publisher): void
    {
        $reversible = (int)($publisher instanceof ReversiblePublisher);
        $transactional = (int)($publisher instanceof TransactionalPublisher);
        // Padding 6 zeros allows for a max of 2^6 publishers = 64
        $maxPublisherCount = 2 ** self::PUBLISHER_PADDING;
        $count = count($this->publishers);
        if ($count >= $maxPublisherCount) {
            throw new PublisherOverflowException($publisher, $maxPublisherCount);
        }
        $index = str_pad(decbin($count), self::PUBLISHER_PADDING, '0', STR_PAD_LEFT);

        // Prefer anything which is reversible and transactional, then
        // only reversible, then only transactional, then the rest.
        $orderMask = $reversible . $transactional . $index;
        // Convert to decimal number to prevent int type cast as array key (PHP does that automatically)
        $index = bindec($orderMask);

        $this->publishers[$index] = $publisher;
        krsort($this->publishers);
    }

    public function canPublish(Record $record): bool
    {
        foreach ($this->publishers as $publisher) {
            if ($publisher->canPublish($record)) {
                return true;
            }
        }
        return false;
    }

    public function publish(Record $record): void
    {
        foreach ($this->publishers as $publisher) {
            if ($publisher->canPublish($record)) {
                $publisher->publish($record);
                return;
            }
        }

        throw new PublisherNotFoundException($record);
    }

    public function finish(): void
    {
        foreach ($this->publishers as $publisher) {
            if ($publisher instanceof FinishablePublisher) {
                $publisher->finish();
            }
        }
    }

    public function reverse(): void
    {
        foreach ($this->publishers as $publisher) {
            if ($publisher instanceof ReversiblePublisher) {
                $publisher->reverse();
            }
        }
    }

    public function start(): void
    {
        foreach ($this->publishers as $publisher) {
            if ($publisher instanceof TransactionalPublisher) {
                $publisher->start();
            }
        }
    }

    public function cancel(): void
    {
        foreach ($this->publishers as $publisher) {
            if ($publisher instanceof TransactionalPublisher) {
                $publisher->cancel();
            }
        }
    }
}
