<?php
namespace In2code\In2publishCore\Domain\Service\Processor;

/***************************************************************
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
 ***************************************************************/

/**
 * Class TextProcessor
 */
class TextProcessor extends AbstractProcessor
{
    const WIZARDS = 'wizards';
    const RTE = 'RTE';

    /**
     * @var bool
     */
    protected $canHoldRelations = true;

    /**
     * @var array
     */
    protected $required = array(
        'only the RTE is supported for relation resolving' => self::WIZARDS,
    );

    /**
     * @param array $config
     * @return bool
     */
    public function canPreProcess(array $config)
    {
        $canPreProcess = parent::canPreProcess($config);
        if ($canPreProcess) {
            if (empty($config[self::WIZARDS][self::RTE])) {
                $this->lastReasons[self::WIZARDS] = 'only the RTE is supported for relation resolving';
                $canPreProcess = false;
            }
        }
        return $canPreProcess;
    }
}
