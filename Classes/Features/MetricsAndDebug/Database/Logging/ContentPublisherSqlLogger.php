<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\MetricsAndDebug\Database\Logging;

use Doctrine\DBAL\Logging\SQLLogger;
use In2code\In2publishCore\Cache\CachedRuntimeCache;

use function array_key_last;
use function array_shift;
use function array_slice;
use function debug_backtrace;
use function get_class;
use function gettype;
use function implode;
use function in_array;
use function is_callable;
use function is_object;
use function is_scalar;
use function str_starts_with;

class ContentPublisherSqlLogger implements SQLLogger
{
    protected const DROP_FRAME = [
        'Doctrine\DBAL\Connection->beginTransaction',
        'Doctrine\DBAL\Connection->commit',
        'Doctrine\DBAL\Connection->executeQuery',
        'Doctrine\DBAL\Connection->executeStatement',
        'Doctrine\DBAL\Connection->delete',
        'Doctrine\DBAL\Query\QueryBuilder->execute',
        'TYPO3\CMS\Core\Database\Query\QueryBuilder->executeQuery',
        'TYPO3\CMS\Core\Database\Query\QueryBuilder->execute',
    ];
    protected static array $queries = [];
    protected float $start;
    protected static int $currentQuery = 0;

    public function startQuery($sql, ?array $params = null, ?array $types = null)
    {
        $this->start = hrtime(true);
        $originalBacktrace = $backtrace = debug_backtrace(0);
        // remove this method
        array_shift($backtrace);

        // Remove methods which lead to the query execution
        for ($removedFrames = 1; $removedFrames < 5; $removedFrames++) {
            $class = $backtrace[0]['class'];
            $type = $backtrace[0]['type'];
            $function = $backtrace[0]['function'];

            $signature = $class . $type . $function;
            if (in_array($signature, self::DROP_FRAME)) {
                array_shift($backtrace);
            } else {
                break;
            }
        }

        $cpFrameIndex = $this->findFirstCpFrame($backtrace);
        if (null !== $cpFrameIndex) {
            $callerFrame = $backtrace[$cpFrameIndex];
            $backtrace = array_slice($backtrace, 0, $cpFrameIndex + 3);
        } else {
            $backtrace = array_slice($backtrace, 0, 7);
            $lastKey = array_key_last($backtrace);
            $callerFrame = $backtrace[$lastKey];
        }

        // Remove args from non-CP methods to reduce memory footprint and "simplify" non-scalar arguments.
        foreach ($backtrace as $index => &$frame) {
            if (isset($frame['class']) && str_starts_with($frame['class'], 'In2code\\In2publish')) {
                $callee = $frame['class'] . $frame['type'] . $frame['function'];
                if (isset($originalBacktrace[$index + $removedFrames - 1])) {
                    $callee .= ' ' . $originalBacktrace[$index + $removedFrames - 1]['line'];
                }
                if (empty($frame['args'])) {
                    $frame = $callee;
                } else {
                    foreach ($frame['args'] as $argIndex => $arg) {
                        if (!is_scalar($arg)) {
                            if (is_object($arg)) {
                                if (is_callable([$arg, '__toString'])) {
                                    $frame['args'][$argIndex] = $arg->__toString();
                                } else {
                                    $frame['args'][$argIndex] = get_class($arg);
                                }
                            } else {
                                $frame['args'][$argIndex] = gettype($arg);
                            }
                        }
                    }
                    $frame['args'] = '[' . implode(', ', $frame['args']) . ']';
                    $frame = [
                        'callee' => $callee,
                        'args' => $frame['args'],
                    ];
                }
            } elseif (isset($frame['class'])) {
                $frame = $frame['class'] . $frame['type'] . $frame['function'];
            }
        }
        unset($frame);

        $entry = [];
        $entry['caller'] = $callerFrame['class'] . $callerFrame['type'] . $callerFrame['function'];
        $entry['sql'] = $sql;
        if (!empty($params)) {
            $entry['params'] = $params;
        }
        if (!empty($types)) {
            $entry['types'] = $types;
        }
        $entry['executionNS'] = 0;
        $entry['backtrace'] = $backtrace;

        self::$queries[++self::$currentQuery] = $entry;
    }

    public function stopQuery(): void
    {
        self::$queries[self::$currentQuery]['executionNS'] = (int)(hrtime(true) - $this->start);
    }

    public static function getQueries(): array
    {
        return self::$queries;
    }

    protected function findFirstCpFrame(array $backtrace): ?int
    {
        foreach ($backtrace as $index => $frame) {
            if (
                isset($frame['class'], $frame['function'])
                && $frame['class'] !== CachedRuntimeCache::class
                && $frame['function'] !== 'executeCached'
                && str_starts_with($frame['class'], 'In2code\\In2publish')
            ) {
                return $index;
            }
        }
        return null;
    }
}
