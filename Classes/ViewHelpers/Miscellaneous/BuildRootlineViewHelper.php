<?php
namespace In2code\In2publishCore\ViewHelpers\Miscellaneous;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 in2code.de
 *  Alex Kellner <alexander.kellner@in2code.de>,
 *  Oliver Eglseder <oliver.eglseder@in2code.de>
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

use TYPO3\CMS\Backend\Utility\BackendUtility as BackendUtilityCore;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class BuildRootlineViewHelper
 */
class BuildRootlineViewHelper extends AbstractViewHelper
{
    /**
     * Build rootline path from given pid
     *        Home/Page1/Page1.1
     *
     * @param int $pageIdentifier
     * @return string
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function render($pageIdentifier = 0)
    {
        $originalDeleteValue = $GLOBALS['TCA']['pages']['ctrl']['delete'];
        $GLOBALS['TCA']['pages']['ctrl']['delete'] = false;

        $rootline = BackendUtilityCore::BEgetRootLine($pageIdentifier);

        $GLOBALS['TCA']['pages']['ctrl']['delete'] = $originalDeleteValue;

        $rootline = array_reverse((array)$rootline);
        $rootlineString = '';
        foreach ($rootline as $pageAttributes) {
            $rootlineString .= $pageAttributes['title'];
            $rootlineString .= '/';
        }
        return $rootlineString;
    }
}
