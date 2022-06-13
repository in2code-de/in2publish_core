<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Publisher;

use Exception;
use In2code\In2publishCore\Domain\Model\Record;

use function bindec;
use function decbin;
use function str_pad;

use const STR_PAD_LEFT;

class PublisherCollection implements ReversiblePublisher, TransactionalPublisher
{
    /**
     * @var array<Publisher>
     */
    protected $publishers = [];

    public function addPublisher(Publisher $publisher): void
    {
        $reversible = (int)($publisher instanceof ReversiblePublisher);
        $transactional = (int)($publisher instanceof TransactionalPublisher);
        // Padding 6 zeros allows for a max of 2^6 publishers = 64
        $count = count($this->publishers);
        if ($count >= 64) {
            throw new Exception('You reached the max count of supported publishers.');
        }
        $index = str_pad(decbin($count), 6, '0', STR_PAD_LEFT);

        // Prefer anything which is reversible and transactional, then
        // only reversible, then only transactional, then the rest.
        $orderMask = $reversible . $transactional . $index;
        // Convert to decimal number to prevent int type cast as array key (PHP does that automatically)
        $index = bindec($orderMask);

        $this->publishers[$index] = $publisher;
        krsort($this->publishers);
    }

    public function publish(Record $record): void
    {
        foreach ($this->publishers as $publisher) {
            if ($publisher->canPublish($record)) {
                $publisher->publish($record);
                return;
            }
        }

        $classification = $record->getClassification();
        $id = $record->getId();

        throw new Exception("Missing publisher for record $classification $id");
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
