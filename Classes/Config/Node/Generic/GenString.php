<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Config\Node\Generic;

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

use function is_string;
use function reset;

/**
 * Class GenString
 */
class GenString extends AbsGenNode
{
    /**
     * @param ValidationContainer $container
     * @param mixed $value
     */
    protected function validateKey(ValidationContainer $container, $value)
    {
        if (!is_string($value)) {
            $container->addError('Key is not a string');
        }
    }

    /**
     * @param array[]|bool[]|int[]|string[] $value
     *
     * @return array[]|bool[]|int[]|string[]
     */
    public function cast($value): array
    {
        $tmp = [];
        foreach ($value as $key => $var) {
            $nodes = $this->nodes->getArrayCopy();
            $tmp[(string)$key] = reset($nodes)->cast($var);
            unset($value[$key]);
        }
        return $tmp;
    }
}
