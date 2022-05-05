<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Service;

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\SingletonInterface;

use function array_keys;

class ColumnNameService implements SingletonInterface
{
    protected const CACHE_ID = 'component_database_columns';

    protected Connection $localDatabase;

    protected FrontendInterface $cache;

    protected $columns = [];

    protected $columnsChanged = false;

    public function injectLocalDatabase(Connection $localDatabase): void
    {
        $this->localDatabase = $localDatabase;
    }

    public function injectCache(FrontendInterface $cache): void
    {
        $this->cache = $cache;
        $this->columns = $this->cache->get(self::CACHE_ID);
    }

    public function getColumnNames(string $table): array
    {
        if (!isset($this->columns[$table])) {
            $this->columns[$table] = array_keys($this->localDatabase->getSchemaManager()->listTableColumns($table));
            $this->columnsChanged = true;
        }
        return $this->columns[$table];
    }

    public function __destruct()
    {
        if ($this->columnsChanged) {
            $this->cache->set(self::CACHE_ID, $this->columns);
        }
    }
}
