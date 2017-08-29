<?php
namespace In2code\In2publishCore\Domain\Service;

/***************************************************************
 * Copyright notice
 *
 * (c) 2015 in2code.de and the following authors:
 *  Alex Kellner <alexander.kellner@in2code.de>
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

use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ExecutionTimeService
 */
class ExecutionTimeService implements SingletonInterface
{
    /**
     * @var float|null
     */
    protected $startTime = null;

    /**
     * @var float|null
     */
    protected $executionTime = null;

    /**
     * Set current microtime
     */
    public function start()
    {
        if (null === $this->startTime) {
            $this->startTime = -microtime(true);
        }
    }

    /**
     * @return float
     */
    public function getExecutionTime()
    {
        if (null === $this->startTime) {
            GeneralUtility::makeInstance(LogManager::class)
                          ->getLogger(static::class)
                          ->notice('Execution time requested before timer was started');
            return 0.0;
        }
        return $this->startTime + microtime(true);
    }
}
