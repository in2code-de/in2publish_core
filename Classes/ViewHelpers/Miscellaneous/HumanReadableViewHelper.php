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

use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * RenderPagePathViewHelper
 *
 * @package In2publish
 * @license http://www.gnu.org/licenses/lgpl.html
 *            GNU Lesser General Public License, version 3 or later
 */
class HumanReadableViewHelper extends AbstractViewHelper
{
    /**
     * @return string
     */
    public function render()
    {
        return $this->convertToHumanReadable($this->renderChildren());
    }

    /**
     * @param mixed $content
     * @return string
     */
    protected function convertToHumanReadable($content)
    {
        $result = '';
        if (is_array($content)) {
            foreach ($content as $key => $value) {
                $result .= $key . ' => ' . $this->convertToHumanReadable($value);
            }
        } else {
            $result .= $content;
        }
        return $result . '<br/>';
    }
}
