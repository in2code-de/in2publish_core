<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\ConfigContainer\Node\Specific;

/*
 * Copyright notice
 *
 * (c) 2022 in2code.de and the following authors:
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

use In2code\In2publishCore\Component\ConfigContainer\ValidationContainer;

use function is_array;

class SpecOptionalArray extends SpecArray
{
    /** @param mixed $value */
    public function validateType(ValidationContainer $container, $value): void
    {
        if (!is_array($value) && null !== $value) {
            $container->addError('The value is not an array');
        }

        if ([] === $value || null === $value) {
            $this->skipValidators();
        }
    }

    /** @param mixed $value */
    public function validate(ValidationContainer $container, $value): void
    {
        $this->validateType($container, $value[$this->name] ?? null);

        if (!$this->validatorsShouldBeSkipped()) {
            $this->validateByValidators($container, $value);
            $this->nodes->validate($container, $value[$this->name]);
        }
    }
}
