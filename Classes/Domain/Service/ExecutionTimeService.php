<?php
namespace In2code\In2publishCore\Domain\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 in2code.de
 *  Alex Kellner <alexander.kellner@in2code.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class ExecutionTimeService
 */
class ExecutionTimeService implements SingletonInterface
{
    /**
     * @var float
     */
    protected $startTime = 0.00;

    /**
     * @var float
     */
    protected $executionTime = 0.00;

    /**
     * Set current microtime
     */
    public function start()
    {
        $this->startTime = -microtime(true);
    }

    public function getExecutionTime()
    {
        $this->stop();
        return $this->executionTime;
    }

    /**
     * Calculates and sets delta
     */
    protected function stop()
    {
        if ($this->startTime < 0) {
            $this->executionTime = $this->startTime + microtime(true);
        }
    }
}
