<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher;

interface TransactionalPublisher extends FinishablePublisher
{
    public function start(): void;

    public function cancel(): void;
}
