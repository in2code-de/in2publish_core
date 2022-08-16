<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Service\Processor;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
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

class InputProcessor extends AbstractProcessor
{
    public const WIZARDS = 'wizards';
    public const INPUT_LINK = 'inputLink';
    public const RENDER_TYPE = 'renderType';

    protected $canHoldRelations = true;

    public function canPreProcess(array $config): bool
    {
        $can = array_key_exists(self::WIZARDS, $config)
            || (array_key_exists(self::RENDER_TYPE, $config) && $config[self::RENDER_TYPE] === self::INPUT_LINK);
        if (!$can) {
            $this->lastReasons['wizards'] = 'This field does not have a wizard';
            $this->lastReasons['renderType'] = 'This fields renderType is not inputLink';
        }
        return $can;
    }
}
