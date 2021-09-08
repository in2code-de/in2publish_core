<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Config\Node;

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

use In2code\In2publishCore\Config\ValidationContainer;
use In2code\In2publishCore\Config\Validator\ValidatorInterface;
use In2code\In2publishCore\In2publishCoreException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_key_exists;
use function array_merge;
use function class_exists;
use function is_array;
use function is_string;

abstract class AbstractNode implements Node
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string[]
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
     * @var bool
     */
    protected $skipValidators = false;

    /**
     * AbstractNode constructor.
     *
     * @param string $name
     * @param string[] $validators
     * @param NodeCollection $nodes
     * @param string|int|bool|array $default
     */
    public function __construct(string $name, array $validators, NodeCollection $nodes, $default)
    {
        $this->name = $name;
        $this->validators = $validators;
        $this->nodes = $nodes;
        $this->default = $default;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $path
     *
     * @return Node
     */
    public function getNodePath(string $path): Node
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
        } else {
            $this->validateType($container, $value[$this->name]);

            if (!$this->validatorsShouldBeSkipped()) {
                $this->validateByValidators($container, $value);
                $this->nodes->validate($container, $value[$this->name]);
            }
        }
    }

    protected function validateByValidators(ValidationContainer $container, $value): void
    {
        foreach ($this->validators as $classOrIndex => $optionsOrClass) {
            if (is_array($optionsOrClass) && is_string($classOrIndex) && class_exists($classOrIndex)) {
                $args = array_merge([$classOrIndex], $optionsOrClass);
            } elseif (class_exists($optionsOrClass)) {
                $args = [$optionsOrClass];
            } else {
                continue;
            }
            $validator = GeneralUtility::makeInstance(...$args);
            if ($validator instanceof ValidatorInterface) {
                $validator->validate($container, $value[$this->name]);
            }
        }
    }

    public function merge(Node $node): void
    {
        if (!empty($node->default)) {
            if (empty($this->default)) {
                $this->default = $node->default;
            } elseif (is_array($this->default) && is_array($node->default)) {
                $this->default = $this->mergeArrays($this->default, $node->default);
            } else {
                throw new In2publishCoreException('Can not merge properties');
            }
        }
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

    public function mergeArrays(array $original, array $additional): array
    {
        return array_merge($original, $additional);
    }

    abstract protected function validateType(ValidationContainer $container, $value): void;

    public function validatorsShouldBeSkipped(): bool
    {
        return $this->skipValidators;
    }

    public function skipValidators(): void
    {
        $this->skipValidators = true;
    }
}
