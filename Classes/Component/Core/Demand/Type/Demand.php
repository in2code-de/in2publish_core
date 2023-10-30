<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Demand\Type;

interface Demand
{
    public function addToDemandsArray(array &$demands): void;

    public function addToMetaArray(array &$meta, array $frame): void;
}
