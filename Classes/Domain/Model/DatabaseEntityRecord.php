<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Model;

interface DatabaseEntityRecord extends Record
{
    public function getPageId(): int;
}
