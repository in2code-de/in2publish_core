<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Service\Database;

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\SingletonInterface;

class DatabaseSchemaService implements SingletonInterface
{
    protected const CACHE_ID = 'component_database_info';

    protected Connection $localDatabase;
    protected FrontendInterface $cache;
    protected $columns = [];
    protected $tables = [];
    protected $infoChanged = false;

    public function injectLocalDatabase(Connection $localDatabase): void
    {
        $this->localDatabase = $localDatabase;
    }

    public function injectCache(FrontendInterface $cache): void
    {
        $this->cache = $cache;
        $cacheData = $this->cache->get(self::CACHE_ID);
        if (isset($cacheData['columns'])) {
            $this->columns = $cacheData['columns'];
        }
        if (isset($cacheData['tables'])) {
            $this->tables = $cacheData['tables'];
        }
    }

    public function getColumnNames(string $table): array
    {
        if (!isset($this->columns[$table])) {
            $columns = $this->localDatabase->getSchemaManager()->listTableColumns($table);
            foreach ($columns as $column) {
                $this->columns[$table][] = $column->getName();
            }
            $this->infoChanged = true;
        }
        return $this->columns[$table];
    }

    public function getTableNames(): array
    {
        if (!isset($this->tables)) {
            $this->tables = $this->localDatabase->getSchemaManager()->listTableNames();
            $this->infoChanged = true;
        }
        return $this->tables;
    }

    public function __destruct()
    {
        if ($this->infoChanged) {
            $cacheData = [
                'columns' => $this->columns,
                'tables' => $this->tables,
            ];
            $this->cache->set(self::CACHE_ID, $cacheData);
        }
    }
}
