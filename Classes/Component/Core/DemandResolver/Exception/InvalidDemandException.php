<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Exception;

use In2code\In2publishCore\Component\Core\Resolver\AbstractResolver;
use In2code\In2publishCore\In2publishCoreException;
use Throwable;

use function array_unique;
use function get_class;
use function implode;
use function sprintf;

/**
 * @codeCoverageIgnore
 */
class InvalidDemandException extends In2publishCoreException
{
    private const MESSAGE = 'An invalid demand caused a query exception "%s" for database exception "%s".%s';
    public const CODE = 1652959570;
    private array $callers;

    public function __construct(array $callers, Throwable $previous)
    {
        $this->callers = $callers;

        $uniqueCallers = [];

        $callerString = '';
        if (!empty($callers)) {
            $callerString .= ' The demand was built by: ';
        }

        foreach ($callers as $caller) {
            $signature = $caller['function'] . '@' . $caller['line'];
            if (isset($caller['class'])) {
                $signature = $caller['class'] . $caller['type'] . $caller['function'];
            }
            if (isset($caller['object'])) {
                $object = $caller['object'];
                if ($object instanceof AbstractResolver) {
                    $meta = $object->getMetaInfo();
                    if (isset($meta['builtBy'])) {
                        $class = $meta['builtBy']['class'];
                        [$table, $column] = $meta['builtBy']['args'];
                        $signature .= " built by processor $class for table \"$table\" and column \"$column\"";
                    }
                }

                $uniqueCallers[] = $signature;
            }
        }

        $callerString .= implode("\n", array_unique($uniqueCallers));

        parent::__construct(
            sprintf(self::MESSAGE, get_class($previous), $previous->getPrevious()->getMessage(), $callerString),
            self::CODE,
            $previous
        );
    }

    public function getCallers(): array
    {
        return $this->callers;
    }
}
