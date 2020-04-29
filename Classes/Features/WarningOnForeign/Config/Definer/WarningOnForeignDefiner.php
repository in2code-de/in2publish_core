<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\WarningOnForeign\Config\Definer;

/*
 * Copyright notice
 *
 * (c) 2020 in2code.de and the following authors:
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

use In2code\In2publishCore\Config\Builder;
use In2code\In2publishCore\Config\Definer\DefinerInterface;
use In2code\In2publishCore\Config\Node\NodeCollection;
use In2code\In2publishCore\Features\WarningOnForeign\Config\Validator\CssColorValueValidator;

class WarningOnForeignDefiner implements DefinerInterface
{
    /**
     * @return NodeCollection
     */
    public function getLocalDefinition()
    {
        return Builder::start()->end();
    }

    /**
     * @return NodeCollection
     */
    public function getForeignDefinition()
    {
        return Builder::start()
                      ->addArray(
                          'features',
                          Builder::start()
                                 ->addArray(
                                     'warningOnForeign',
                                     Builder::start()
                                            ->addArray(
                                                'colorizeHeader',
                                                Builder::start()
                                                       ->addBoolean('enable', false)
                                                       ->addString('color', '#a06e23', [CssColorValueValidator::class])
                                            )
                                 )
                      )
                      ->end();
    }
}
