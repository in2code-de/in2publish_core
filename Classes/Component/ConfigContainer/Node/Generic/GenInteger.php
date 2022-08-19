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

use In2code\In2publishCore\Component\ConfigContainer\ValidationContainer;

use function is_int;
use function reset;

class GenInteger extends AbsGenNode
{
    /** @param mixed $value */
    protected function validateKey(ValidationContainer $container, $value): void
    {
        if (!is_int($value)) {
            $container->addError('Key is not an integer');
        }
    }

    /**
     * @param mixed $value
     *
     * @return array<int>
     */
    public function cast($value): array
    {
        $tmp = [];
        foreach ($value as $key => $var) {
            $nodes = $this->nodes->getArrayCopy();
            $valueNode = reset($nodes);
            $casted = $valueNode->cast($var);
            $tmp[(int)$key] = $casted;
            unset($value[$key]);
        }
        return $tmp;
    }
}
