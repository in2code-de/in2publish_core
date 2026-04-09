<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Service\Database;

use In2code\In2publishCore\CommonInjection\CacheInjection;
use In2code\In2publishCore\CommonInjection\LocalDatabaseInjection;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\SingletonInterface;

use function is_array;

class DatabaseSchemaService implements SingletonInterface
{
    use LocalDatabaseInjection;
    use CacheInjection;

    protected const CACHE_ID = 'component_database_info';
    protected array $columns = [];
    protected ?array $tables = null;
    protected bool $infoChanged = false;

    public function injectCache(FrontendInterface $cache): void
    {
        $this->cache = $cache;
        $cacheData = $cache->get(self::CACHE_ID);
        if (is_array($cacheData)) {
            $this->columns = $cacheData['columns'] ?? [];
            $this->tables = $cacheData['tables'] ?? null;
        }
    }

    public function getColumnNames(string $table): array
    {
        if (!isset($this->columns[$table])) {
            $columns = $this->localDatabase->getSchemaInformation()->listTableColumnNames($table);
            foreach ($columns as $columnName) {
                $this->columns[$table][] = $columnName;
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
