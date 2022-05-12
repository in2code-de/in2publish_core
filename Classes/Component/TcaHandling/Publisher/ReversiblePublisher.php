<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Publisher;

interface ReversiblePublisher
{
    public function reverse(): void;
}
