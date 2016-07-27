<?php
namespace In2code\In2publishCore\ViewHelpers\Be;

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

use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class GetClassNameOnActionViewHelper
 *
 * @package In2code\In2publish\ViewHelpers\Be
 */
class GetClassNameOnActionViewHelper extends AbstractViewHelper
{
    /**
     * Return className if actionName fits to current action
     *
     * @param string $actionName action name to compare with current action
     * @param string $className classname that should be returned if action fits
     * @param string $fallbackClassName fallback classname if action does not fit
     * @return string
     */
    public function render($actionName, $className = ' btn-primary', $fallbackClassName = ' btn-default')
    {
        if ($this->getCurrentActionName() === $actionName) {
            return $className;
        }
        return $fallbackClassName;
    }

    /**
     * @return string
     */
    protected function getCurrentActionName()
    {
        return $this->controllerContext->getRequest()->getControllerActionName();
    }
}
