<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Record\Model;

interface DatabaseEntityRecord extends Record
{
    public function getPageId(): int;
}
