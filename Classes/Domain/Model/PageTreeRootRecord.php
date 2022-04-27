<?php

namespace In2code\In2publishCore\Domain\Model;

class PageTreeRootRecord extends DatabaseRecord
{
    public function __construct()
    {
        parent::__construct('pages', 0, [], [], []);
    }

    public function getState(): string
    {
        return Record::S_UNCHANGED;
    }

    public function getPageId(): int
    {
        return 0;
    }

}
