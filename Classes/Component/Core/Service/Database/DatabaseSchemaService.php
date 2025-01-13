<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Service\Database;

use In2code\In2publishCore\CommonInjection\CacheInjection;
use In2code\In2publishCore\CommonInjection\LocalDatabaseInjection;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\SingletonInterface;

class DatabaseSchemaService implements SingletonInterface
{
    use LocalDatabaseInjection;
    use CacheInjection {
        injectCache as actualInjectCache;
    }

    protected const CACHE_ID = 'component_database_info';
    protected array $columns = [];
    protected ?array $tables = null;
    protected bool $infoChanged = false;

    /**
     * @noinspection PhpUnused
     */
    public function injectCache(FrontendInterface $cache): void
    {
        $this->actualInjectCache($cache);
        $cacheData = $cache->get(self::CACHE_ID);
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
            $columns = $this->localDatabase->getSchemaInformation()->introspectTable($table)->getColumns();
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
            $this->tables = $this->localDatabase->getSchemaInformation()->listTableNames();
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
