<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Demand;

use In2code\In2publishCore\Component\Core\Demand\Type\Demand;

use function debug_backtrace;
use function get_class;

use const DEBUG_BACKTRACE_PROVIDE_OBJECT;

class CallerAwareDemandsCollection extends DemandsCollection
{
    private array $meta = [];

    public function addDemand(Demand $demand): void
    {
        parent::addDemand($demand);
        // Support for deprecated methods. If the backtrace points to the collection itself,
        // the addDemand method call was converted by a deprecated method.
        // Use `$frame =debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0];` in in2publish_core v13.
        $frames = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $frame = $frames[0];
        if ($frame['class'] === self::class) {
            $frame = $frames[1];
        }
        $this->meta[get_class($demand)] ??= [];
        $meta = &$this->meta[get_class($demand)];
        $demand->addToMetaArray($meta, $frame);
    }

    public function getMeta(...$keys): array
    {
        $meta = $this->meta;
        foreach ($keys as $key) {
            $meta = $meta[$key] ?? [];
        }
        return $meta;
    }
}
