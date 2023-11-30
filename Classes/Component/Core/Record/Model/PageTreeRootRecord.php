<?php

namespace In2code\In2publishCore\Component\Core\Record\Model;

class PageTreeRootRecord extends DatabaseRecord
{
    public function __construct()
    {
        parent::__construct('pages', 0, [], [], []);
    }

    protected function calculateState(): string
    {
        return Record::S_UNCHANGED;
    }

    public function getPageId(): int
    {
        return 0;
    }

    public function __toString(): string
    {
        return $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
    }
}
