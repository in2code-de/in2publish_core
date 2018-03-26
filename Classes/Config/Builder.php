<?php
namespace In2code\In2publishCore\Config;

/***************************************************************
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
 ***************************************************************/

use In2code\In2publishCore\Config\Node\Generic\AbsGenNode;
use In2code\In2publishCore\Config\Node\Node;
use In2code\In2publishCore\Config\Node\NodeCollection;
use In2code\In2publishCore\Config\Node\Specific\AbsSpecNode;
use In2code\In2publishCore\Config\Validator\ValidatorInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Builder
 */
class Builder
{
    /**
     * @var NodeCollection
     */
    protected $nodes = [];

    /**
     * @var string[]
     */
    protected $path = [];

    /**
     * @var int
     */
    protected $depth = 0;

    /**
     * Builder constructor.
     */
    public function __construct()
    {
        $this->nodes = new NodeCollection();
    }

    /**
     * @return static
     */
    public static function start()
    {
        return GeneralUtility::makeInstance(static::class);
    }

    /**
     * @return NodeCollection
     */
    public function end()
    {
        return $this->nodes;
    }

    /**
     * @param string $name
     * @param Builder $nodes
     * @param null $default
     * @param array $validators
     * @return $this
     */
    public function addArray($name, Builder $nodes, $default = null, array $validators = [])
    {
        $this->addNode(Node::T_ARRAY, $name, $default, $validators, $nodes);
        return $this;
    }

    /**
     * @param string $name
     * @param Builder $nodes
     * @param null $default
     * @param array $validators
     * @return $this
     */
    public function addStrictArray($name, Builder $nodes, $default = null, array $validators = [])
    {
        $this->addNode(Node::T_STRICT_ARRAY, $name, $default, $validators, $nodes);
        return $this;
    }

    /**
     * @param string $key
     * @param string $default
     * @param array $validators
     * @return $this
     */
    public function addString($key, $default, array $validators = [])
    {
        $this->addNode(Node::T_STRING, $key, $default, $validators);
        return $this;
    }

    /**
     * @param string $key
     * @param int $default
     * @param array $validators
     * @return $this
     */
    public function addInteger($key, $default, array $validators = [])
    {
        $this->addNode(Node::T_INTEGER, $key, $default, $validators);
        return $this;
    }

    /**
     * @param string $key
     * @param bool $default
     * @param array $validators
     * @return $this
     */
    public function addBoolean($key, $default, array $validators = [])
    {
        $this->addNode(Node::T_BOOLEAN, $key, $default, $validators);
        return $this;
    }

    /**
     * @param string $type
     * @param string $name
     * @param string|int|bool|array $default
     * @param ValidatorInterface[] $validators
     * @param Builder|null $builder
     * @return $this
     */
    public function addNode($type, $name, $default = null, $validators = [], Builder $builder = null)
    {
        if ($builder instanceof Builder) {
            $nodes = $builder->end();
        } else {
            $nodes = GeneralUtility::makeInstance(NodeCollection::class);
        }
        $node = AbsSpecNode::fromType($type, $name, $default, $validators, $nodes);
        $this->nodes->addNode($node);
        return $this;
    }

    /**
     * @param string $keyType
     * @param string $type
     * @return $this
     */
    public function addGenericScalar($keyType, $type = Node::T_STRING)
    {
        $valueNode = Builder::start()->addNode($type, '*:' . $type)->end();
        $keyNode = AbsGenNode::fromType($keyType, '*:' . $keyType, $valueNode, null);
        $this->nodes->addNode($keyNode);
        return $this;
    }

    /**
     * @param string $keyType
     * @param Builder $nodes
     * @return $this
     */
    public function addGenericArray($keyType, Builder $nodes)
    {
        $valueNodes = Builder::start()->addArray('*:' . $keyType, $nodes)->end();
        $keyNode = AbsGenNode::fromType($keyType, '*:' . $keyType, $valueNodes, null);
        $this->nodes->addNode($keyNode);
        return $this;
    }
}
