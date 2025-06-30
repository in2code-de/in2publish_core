<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\ConfigContainer\Node\Generic;

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

use In2code\In2publishCore\Component\ConfigContainer\Node\AbstractNode;
use In2code\In2publishCore\Component\ConfigContainer\Node\Node;
use In2code\In2publishCore\Component\ConfigContainer\Node\NodeCollection;
use In2code\In2publishCore\Component\ConfigContainer\ValidationContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbsGenNode extends AbstractNode
{
    /**
     * @var string[]
     */
    protected static array $types = [
        Node::T_STRING => GenString::class,
        Node::T_INTEGER => GenInteger::class,
    ];

    /**
     * @return GenString|GenInteger
     */
    public static function fromType(string $type, string $name, NodeCollection $nodes, string|int|bool|array|null $default = null): AbsGenNode
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return GeneralUtility::makeInstance(static::$types[$type], $name, [], $nodes, $default);
    }

    /** @param mixed $value */
    public function validate(ValidationContainer $container, $value): void
    {
        foreach ($value as $key => $item) {
            $this->validateKey($container, $key);
            $this->validateType($container, $item);
        }
    }

    /** @param mixed $value */
    protected function validateType(ValidationContainer $container, $value): void
    {
        foreach ($this->nodes as $node) {
            $container->validate($node, [$node->getName() => $value]);
        }
    }

    /**
     * Do not recurse into nodes. Generic child nodes must not have defaults.
     *
     * @return string[]|int[]|bool[]|array[]
     */
    public function getDefaults(): array
    {
        return [];
    }

    /**
     * Generic values don't have defaults.
     *
     * @param array[]|bool[]|int[]|string[] $value
     */
    public function unsetDefaults(array &$value): void
    {
    }

    /**
     * @param ValidationContainer $container
     * @param mixed $value
     */
    abstract protected function validateKey(ValidationContainer $container, $value): void;
}
