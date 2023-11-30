<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher\Instruction;

use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\FalDriverService;

interface PublishInstruction
{
    public function execute(FalDriverService $falDriverService): void;

    public function getConfiguration(): array;
}
