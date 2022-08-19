<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\ConfigContainer;

/*
 * Copyright notice
 *
 * (c) 2018 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use In2code\In2publishCore\Component\ConfigContainer\Node\Generic\AbsGenNode;
use In2code\In2publishCore\Component\ConfigContainer\Node\Node;
use In2code\In2publishCore\Component\ConfigContainer\Node\NodeCollection;
use In2code\In2publishCore\Component\ConfigContainer\Node\Specific\AbsSpecNode;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Builder
{
    protected NodeCollection $nodes;

    public function __construct()
    {
        $this->nodes = new NodeCollection();
    }

    public static function start(): Builder
    {
        return GeneralUtility::makeInstance(static::class);
    }

    public function end(): NodeCollection
    {
        return $this->nodes;
    }

    public function addArray(string $name, Builder $nodes, array $default = null, array $validators = []): self
    {
        $this->addNode(Node::T_ARRAY, $name, $default, $validators, $nodes);
        return $this;
    }

    public function addStrictArray(string $name, Builder $nodes, array $default = null, array $validators = []): self
    {
        $this->addNode(Node::T_STRICT_ARRAY, $name, $default, $validators, $nodes);
        return $this;
    }

    public function addString(string $key, string $default, array $validators = []): self
    {
        $this->addNode(Node::T_STRING, $key, $default, $validators);
        return $this;
    }

    public function addOptionalArray(
        string $name,
        Builder $nodes,
        array $default = null,
        array $validators = []
    ): Builder {
        $this->addNode(Node::T_OPTIONAL_ARRAY, $name, $default, $validators, $nodes);
        return $this;
    }

    public function addOptionalString(string $key, string $default, array $validators = []): self
    {
        $this->addNode(Node::T_OPTIONAL_STRING, $key, $default, $validators);
        return $this;
    }

    public function addInteger(string $key, int $default, array $validators = []): self
    {
        $this->addNode(Node::T_INTEGER, $key, $default, $validators);
        return $this;
    }

    public function addBoolean(string $key, bool $default, array $validators = []): self
    {
        $this->addNode(Node::T_BOOLEAN, $key, $default, $validators);
        return $this;
    }

    /** @param string|int|bool|array|null $default */
    public function addNode(
        string $type,
        string $name,
        $default = null,
        array $validators = [],
        Builder $builder = null
    ): self {
        if ($builder instanceof self) {
            $nodes = $builder->end();
        } else {
            $nodes = GeneralUtility::makeInstance(NodeCollection::class);
        }
        $node = AbsSpecNode::fromType($type, $name, $default, $validators, $nodes);
        $this->nodes->addNode($node);
        return $this;
    }

    public function addGenericScalar(string $keyType, string $type = Node::T_STRING): self
    {
        $valueNode = self::start()->addNode($type, '*:' . $type)->end();
        $keyNode = AbsGenNode::fromType($keyType, '*:' . $keyType, $valueNode);
        $this->nodes->addNode($keyNode);
        return $this;
    }

    public function addGenericArray(string $keyType, Builder $nodes): self
    {
        $valueNodes = self::start()->addArray('*:' . $keyType, $nodes)->end();
        $keyNode = AbsGenNode::fromType($keyType, '*:' . $keyType, $valueNodes);
        $this->nodes->addNode($keyNode);
        return $this;
    }
}
