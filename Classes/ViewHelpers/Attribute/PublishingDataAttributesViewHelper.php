<?php
namespace In2code\In2publishCore\ViewHelpers\Attribute;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 in2code.de
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

use In2code\In2publishCore\Utility\ConfigurationUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class PublishingDataAttributesViewHelper
 */
class PublishingDataAttributesViewHelper extends AbstractViewHelper
{
    /**
     * Get data attributes for publishing link
     *
     * @param array $attributes
     * @return array
     */
    public function render(array $attributes = [])
    {
        $attributes['data-in2publish-confirm'] = LocalizationUtility::translate(
            'confirm_publish_pages',
            'in2publish_core'
        );
        if (ConfigurationUtility::getConfiguration('features.publish.fireAndForget.enable') !== true) {
            $attributes['data-in2publish-overlay'] = 'TRUE';
        } else {
            $attributes['data-action-ajax-uri'] = 'href';
            $attributes['data-action-ajax-once'] = 'true';
            $attributes['data-action-ajax-callback-start'] = 'publishingStart';
            $attributes['data-action-ajax-callback-done'] = 'publishingDone';
        }
        return $attributes;
    }
}
