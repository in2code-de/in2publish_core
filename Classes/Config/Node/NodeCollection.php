<?php
namespace In2code\In2publishCore\Config\Node;

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
use In2code\In2publishCore\Config\Node\Specific\AbsSpecNode;
use In2code\In2publishCore\Config\Node\Specific\SpecArray;
use In2code\In2publishCore\Config\ValidationContainer;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Class NodeCollection
 */
class NodeCollection extends \ArrayObject implements Node
{
    /**
     * NodeCollection constructor.
     *
     * @param Node[] $nodes
     */
    public function __construct(array $nodes = [])
    {
        foreach ($nodes as $node) {
            $this->addNode($node);
        }
    }

    /**
     * @param Node $node
     */
    public function addNode(Node $node)
    {
        $name = $node->getName();
        if ($this->offsetExists($name)) {
            /** @var Node $originalNode */
            $originalNode = $this->offsetGet($name);
            $originalNode->merge($node);
        } else {
            $this->offsetSet($name, $node);
        }
    }

    /**
     * @param string $path
     * @return Node|NodeCollection
     */
    public function getNodePath($path)
    {
        if (empty($path)) {
            return $this;
        }
        $parts = explode('.', $path);
        $index = array_shift($parts);
        if ($this->offsetExists($index)) {
            /** @var Node $node */
            $node = $this->offsetGet($index);
            return $node->getNodePath(implode(',', $parts));
        } else {
            $node = AbsSpecNode::fromType(Node::T_ARRAY, $index, null, [], new NodeCollection());
            $this->addNode($node);
            return $node->getNodePath(implode(',', $parts));
        }
    }

    /**
     * @param NodeCollection $nodes
     */
    public function addNodes(NodeCollection $nodes)
    {
        foreach ($nodes as $node) {
            $this->addNode($node);
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return spl_object_hash($this);
    }

    /**
     * @param ValidationContainer $container
     * @param mixed $value
     */
    public function validate(ValidationContainer $container, $value)
    {
        /** @var Node $node */
        foreach ($this as $node) {
            $container->validate($node, $value);
        }
    }

    /**
     * @return string[]|int[]|bool[]|array[]
     */
    public function getDefaults()
    {
        $defaults = [];
        /** @var Node $node */
        foreach ($this as $node) {
            $nodeDefaults = $node->getDefaults();
            ArrayUtility::mergeRecursiveWithOverrule($defaults, $nodeDefaults);
        }
        return $defaults;
    }

    /**
     * NodeCollections will never "collide" because they don't have names
     *
     * @param Node $node
     */
    public function merge(Node $node)
    {
    }

    /**
     * @param array[]|bool[]|int[]|string[] $value
     * @return array[]|bool[]|int[]|string[]
     */
    public function cast($value)
    {
        $tmp = [];
        foreach ($this as $key => $node) {
            if (!is_array($value) && $node instanceof AbsGenNode) {
                // empty non-array values are considered empty in generic structures.
                // Return the array to fix the data type.
            } elseif (array_key_exists($key, $value)) {
                $tmp[$key] = $node->cast($value[$key]);
            } elseif ($node instanceof AbsGenNode) {
                $tmp = $node->cast($value);
            }
        }
        return $tmp;
    }

    /**
     * @param array[]|bool[]|int[]|string[] $value
     */
    public function unsetDefaults(&$value)
    {
        /** @var Node $node */
        foreach ($this as $key => $node) {
            if (array_key_exists($key, $value)) {
                $node->unsetDefaults($value);
            } elseif ($node instanceof AbsGenNode) {
                $node->unsetDefaults($value);
            }
        }
    }
}
