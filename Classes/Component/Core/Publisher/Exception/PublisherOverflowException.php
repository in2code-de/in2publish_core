<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher\Exception;

use In2code\In2publishCore\Component\Core\Publisher\Publisher;
use In2code\In2publishCore\In2publishCoreException;
use Throwable;

use function get_class;
use function sprintf;

class PublisherOverflowException extends In2publishCoreException
{
    private const MESSAGE = 'Tried to add the publisher "%s", but you can not add more than %d publishers. Please remove one or more publisher.';
    public const CODE = 1657192385;
    private Publisher $publisher;
    private int $count;

    public function __construct(Publisher $publisher, int $count, Throwable $previous = null)
    {
        $this->publisher = $publisher;
        $this->count = $count;
        parent::__construct(sprintf(self::MESSAGE, get_class($publisher), $count), self::CODE, $previous);
    }

    public function getPublisher(): Publisher
    {
        return $this->publisher;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
