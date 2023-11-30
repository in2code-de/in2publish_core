<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher;

interface FinishablePublisher extends Publisher
{
    public function finish(): void;
}
