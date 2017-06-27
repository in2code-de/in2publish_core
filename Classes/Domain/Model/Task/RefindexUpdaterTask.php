<?php
namespace In2code\In2publishCore\Domain\Model\Task;

/***************************************************************
 *  Copyright notice
 *
 * (c) 2017 in2code.de and the following authors:
 *  Holger Krämer <post@holgerkraemer.com>
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

use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class RefindexUpdaterTask
 */
class RefindexUpdaterTask extends AbstractTask
{
    /**
     * Don't modify configuration
     *
     * @return void
     */
    public function modifyConfiguration()
    {
    }

    /**
     * Update sys_refindex for given records
     *
     *        expected:
     *        $this->configuration = [
     *            'uid' => '1234567'
     *            'table' => 'tt_content'
     *        ]
     *
     * @return bool
     */
    protected function executeTask()
    {
        /** @var $refIndexObj ReferenceIndex */
        $refIndexObj = GeneralUtility::makeInstance(ReferenceIndex::class);

        $refIndexObj->updateRefIndexTable($this->configuration['table'], $this->configuration['uid']);

        return true;
    }
}
