<?php
namespace In2code\In2publishCore\Config\Validator;

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

/**
 * Class IntegerInRangeValidator
 */
class IntegerInRangeValidator implements ValidatorInterface
{
    /**
     * @var int
     */
    protected $min;

    /**
     * @var int
     */
    protected $max;

    /**
     * IntegerInRangeValidator constructor.
     *
     * @param int $min
     * @param int $max
     */
    public function __construct($min = PHP_INT_MIN, $max = PHP_INT_MAX)
    {
        $this->min = $min;
        $this->max = $max;
    }

    /**
     * @param ValidationContainer $container
     * @param $value
     */
    public function validate(ValidationContainer $container, $value)
    {
        if ($this->min > $value || $value > $this->max) {
            $container->addError("The value $value is not in the range of $this->min to $this->max");
        }
    }
}
