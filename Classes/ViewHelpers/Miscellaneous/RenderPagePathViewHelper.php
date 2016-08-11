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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class RenderPagePathViewHelper
 */
class RenderPagePathViewHelper extends AbstractViewHelper
{
    /**
     * @var \TYPO3\CMS\Core\Resource\Folder
     */
    protected $currentFolder = null;

    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceFactory
     * @inject
     */
    protected $fileFactory = null;

    /**
     * Get filter status
     *
     * @return bool
     */
    public function render()
    {
        $path = '/';
        if ($this->currentFolder !== null) {
            $path = $this->currentFolder->getIdentifier();
        }
        return $path;
    }

    public function initializeObject()
    {
        $combinedIdentifier = GeneralUtility::_GP('id');
        if ($combinedIdentifier !== null) {
            $this->currentFolder = $this->fileFactory->getFolderObjectFromCombinedIdentifier($combinedIdentifier);
        }
    }
}
