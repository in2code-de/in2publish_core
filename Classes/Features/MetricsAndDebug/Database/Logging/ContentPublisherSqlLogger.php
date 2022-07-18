<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\MetricsAndDebug\Database\Logging;

use Doctrine\DBAL\Logging\SQLLogger;

use function array_key_last;
use function array_shift;
use function debug_backtrace;
use function microtime;

use const DEBUG_BACKTRACE_IGNORE_ARGS;

class ContentPublisherSqlLogger implements SQLLogger
{
    protected static array $queries = [];
    protected float $start;
    protected int $currentQuery = 0;

    public function startQuery($sql, array $params = null, array $types = null)
    {
        $this->start = microtime(true);
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 7);
        // remove this method
        array_shift($backtrace);
        // remove doctrine execute query
        array_shift($backtrace);
        // remove queryBuilder execute
        array_shift($backtrace);

        $lastKey = array_key_last($backtrace);
        $lastValue = $backtrace[$lastKey];

        $string = $lastValue['class'] . $lastValue['type'] . $lastValue['function'];

        self::$queries[++$this->currentQuery] = [
            'caller' => $string,
            'sql' => $sql,
            'params' => $params,
            'types' => $types,
            'executionMS' => 0,
            'backtrace' => $backtrace,
        ];
    }

    public function stopQuery(): void
    {
        self::$queries[$this->currentQuery]['executionMS'] = microtime(true) - $this->start;
    }

    public static function getQueries(): array
    {
        return self::$queries;
    }
}
