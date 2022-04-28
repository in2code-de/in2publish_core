<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Model;

interface Node
{
    public function getClassification(): string;

    /**
     * @return array-key
     */
    public function getId();

    public function addChild(Record $childRecord): void;
}
