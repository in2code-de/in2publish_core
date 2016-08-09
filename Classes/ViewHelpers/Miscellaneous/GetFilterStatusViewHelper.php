<?php
namespace In2code\In2publishCore\ViewHelpers\Miscellaneous;

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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * GetFilterStatusViewHelper
 *
 * @package In2publish
 * @license http://www.gnu.org/licenses/lgpl.html
 *            GNU Lesser General Public License, version 3 or later
 */
class GetFilterStatusViewHelper extends AbstractViewHelper
{
    /**
     * @var BackendUserAuthentication
     */
    protected $backendUser = null;

    /**
     * Get filter status
     *
     * @param string $filter
     * @param string $key "records" or "files"
     * @return bool
     */
    public function render($filter, $key = 'records')
    {
        return $this->backendUser->getSessionData('in2publish_filter_' . $key . '_' . $filter) === true;
    }

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize()
    {
        $this->backendUser = $this->getBackendUser();
    }

    /**
     * @return BackendUserAuthentication
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
