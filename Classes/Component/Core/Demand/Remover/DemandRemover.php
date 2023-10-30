<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Demand\Remover;

interface DemandRemover
{
    public function getDemandType(): string;

    public function removeFromDemandsArray(array &$demands): void;
}
