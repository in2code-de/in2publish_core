<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Record\Model;

interface Node
{
    public function getClassification(): string;

    /**
     * @return array-key
     */
    public function getId();

    public function addChild(Record $record): void;
}
