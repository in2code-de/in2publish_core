<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\PreProcessing;

interface TcaPreProcessor
{
    public function getType(): string;

    public function getTable(): string;

    public function getColumn(): string;

    public function process(string $table, string $column, array $tca): ProcessingResult;
}
