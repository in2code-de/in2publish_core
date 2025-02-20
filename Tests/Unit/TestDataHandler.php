<?php

namespace In2code\In2publishCore\Tests\Unit;

use TYPO3\CMS\Core\DataHandling\DataHandler;

class TestDataHandler extends DataHandler
{
    public function checkModifyAccessList(string $table): bool
    {
        return parent::checkModifyAccessList($table);
    }

    public function isInWebMount($pid)
    {
        return parent::isInWebMount($pid);
    }
}