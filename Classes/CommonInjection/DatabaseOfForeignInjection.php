<?php

declare(strict_types=1);

namespace In2code\In2publishCore\CommonInjection;

use TYPO3\CMS\Core\Database\Connection;

/**
 * This always injects Foreign's database, regardless if injected on Local or Foreign.
 * @see \In2code\In2publishCore\Factory\ConnectionFactory::createOtherConnection()
 *
 * @codeCoverageIgnore
 */
trait DatabaseOfForeignInjection
{
    protected Connection $databaseOfForeign;

    /**
     * @noinspection PhpUnused
     */
    public function injectOtherDatabase(Connection $databaseOfForeign): void
    {
        $this->databaseOfForeign = $databaseOfForeign;
    }
}
