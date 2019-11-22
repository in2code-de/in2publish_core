<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Config\Node\Specific;

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

use In2code\In2publishCore\Config\Node\AbstractNode;
use In2code\In2publishCore\Config\Node\Node;
use In2code\In2publishCore\Config\Node\NodeCollection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function array_key_exists;

/**
 * Class AbsSpecNode
 */
abstract class AbsSpecNode extends AbstractNode
{
    /**
     * @var array
     */
    protected static $types = [
        Node::T_STRING => SpecString::class,
        Node::T_OPTIONAL_STRING => SpecOptionalString::class,
        Node::T_INTEGER => SpecInteger::class,
        Node::T_ARRAY => SpecArray::class,
        Node::T_STRICT_ARRAY => SpecStrictArray::class,
        Node::T_BOOLEAN => SpecBoolean::class,
    ];

    /**
     * @param string $type
     * @param string $name
     * @param string|int|bool|array $default
     * @param array $validators
     * @param NodeCollection|null $nodes
     *
     * @return AbsSpecNode|object
     */
    public static function fromType($type, $name, $default, array $validators, NodeCollection $nodes)
    {
        return GeneralUtility::makeInstance(static::$types[$type] ?: $type, $name, $validators, $nodes, $default);
    }

    /**
     * @return string[]|int[]|bool[]|array[]
     */
    public function getDefaults(): array
    {
        return [$this->name => $this->default];
    }

    /**
     * @param array[]|bool[]|int[]|string[] $value
     */
    public function unsetDefaults(array &$value)
    {
        if (null !== $this->default
            && array_key_exists($this->name, $value)
            && $this->default === $value[$this->name]
        ) {
            unset($value[$this->name]);
        }
    }
}
