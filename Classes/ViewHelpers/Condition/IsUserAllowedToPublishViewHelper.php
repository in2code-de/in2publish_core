<?php
namespace In2code\In2publishCore\ViewHelpers\Condition;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 in2code.de
 *  Alex Kellner <alexander.kellner@in2code.de>,
 *  Oliver Eglseder <oliver.eglseder@in2code.de>
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * IsUserAllowedToPublishViewHelper
 *
 * @package In2publish
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class IsUserAllowedToPublishViewHelper extends AbstractViewHelper
{
    /**
     * @var Dispatcher
     */
    protected $dispatcher = null;

    /**
     * IsUserAllowedToPublishViewHelper constructor.
     */
    public function __construct()
    {
        $this->dispatcher = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
    }

    /**
     * Check if user is allowed to publish
     *
     * @return bool
     */
    public function render()
    {
        $votes = array(
            'yes' => 0,
            'no' => 0,
        );
        $votes = $this->voteUserIsAllowedToPublish($votes);
        return $votes['yes'] >= $votes['no'];
    }

    /**
     * @param array $votes
     * @return array
     */
    protected function voteUserIsAllowedToPublish(array $votes)
    {
        // votes are manipulated via reference
        $voteResult = $this->dispatcher->dispatch(__CLASS__, __FUNCTION__, array($votes));
        if (isset($voteResult[0])) {
            return $voteResult[0];
        }
        return $votes;
    }
}
