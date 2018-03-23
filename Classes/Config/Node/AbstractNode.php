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

use In2code\In2publishCore\Config\ValidationContainer;
use In2code\In2publishCore\Config\Validator\ValidatorInterface;

/**
 * Class AbstractNode
 */
abstract class AbstractNode implements Node
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var ValidatorInterface[]
     */
    protected $validators;

    /**
     * @var NodeCollection
     */
    protected $nodes;

    /**
     * @var string|int|bool|array
     */
    protected $default;

    /**
     * AbstractNode constructor.
     *
     * @param string $name
     * @param ValidatorInterface[] $validators
     * @param NodeCollection $nodes
     * @param string|int|bool|array $default
     */
    public function __construct($name, array $validators, NodeCollection $nodes, $default)
    {
        $this->name = $name;
        $this->validators = $validators;
        $this->nodes = $nodes;
        $this->default = $default;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $path
     * @return Node
     */
    public function getNodePath($path)
    {
        return $this->nodes->getNodePath($path);
    }

    /**
     * @param Node $node
     */
    public function addNode(Node $node)
    {
        $this->nodes->addNode($node);
    }

    /**
     * @param ValidationContainer $container
     * @param mixed $value
     */
    public function validate(ValidationContainer $container, $value)
    {
        if (!is_array($value)) {
            $container->addError('Configuration format is wrong');
        } elseif (!array_key_exists($this->name, $value)) {
            $container->addError('Configuration value is not set');
        } elseif ('' === $value[$this->name] || null === $value[$this->name]) {
            $container->addError('Configuration value must not be empty');
        } else {
            $this->validateType($container, $value[$this->name]);
            foreach ($this->validators as $validator) {
                $validator->validate($container, $value[$this->name]);
            }
            $this->nodes->validate($container, $value[$this->name]);
        }
    }

    /**
     * @param Node $node
     */
    public function merge(Node $node)
    {
        foreach ($node->getNodePath('') as $key => $newNode) {
            if (isset($node->nodes[$key])) {
                if (isset($this->nodes[$key])) {
                    $this->nodes[$key]->merge($newNode);
                } else {
                    $this->addNode($newNode);
                }
            }
        }
    }

    /**
     * @param ValidationContainer $container
     * @param mixed $value
     */
    abstract protected function validateType(ValidationContainer $container, $value);
}
