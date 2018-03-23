<?php
namespace In2code\In2publishCore\Config\Node\Specific;

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
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Class SpecArray
 */
class SpecArray extends AbsSpecNode
{
    /**
     * @param ValidationContainer $container
     * @param mixed $value
     */
    public function validateType(ValidationContainer $container, $value)
    {
        if (!is_array($value)) {
            $container->addError('The value is not an array');
        }
    }

    /**
     * @return string[]|int[]|bool[]|array[]
     */
    public function getDefaults()
    {
        $defaults = [];
        if (null !== $this->default) {
            $defaults = [$this->name => $this->default];
        }
        $nodeDefaults = $this->nodes->getDefaults();
        if (!isset($defaults[$this->name])) {
            $defaults[$this->name] = [];
        }
        ArrayUtility::mergeRecursiveWithOverrule($defaults[$this->name], $nodeDefaults);
        return $defaults;
    }

    /**
     * @param array|bool|int|string $value
     *
     * @return array
     */
    public function cast($value)
    {
        return $this->nodes->cast($value);
    }

    /**
     * @param array[]|bool[]|int[]|string[] $value
     */
    public function unsetDefaults(&$value)
    {
        $this->nodes->unsetDefaults($value[$this->name]);
        if (null !== $this->default) {
            foreach ($this->default as $defKey => $defValue) {
                if (array_key_exists($defKey, $value[$this->name])) {
                    if ($value[$this->name][$defKey] === $defValue) {
                        unset($value[$this->name][$defKey]);
                    }
                }
            }
        }
        if (empty($value[$this->name])) {
            unset($value[$this->name]);
        }
    }
}
