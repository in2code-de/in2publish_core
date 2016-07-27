<?php
namespace In2code\In2publishCore\ViewHelpers\Repository;

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

use In2code\In2publishCore\Domain\Repository\LocalRepository;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * GetIdentifierNameByJsonDataViewHelper
 *
 * @package In2code.In2publish
 * @license http://www.gnu.org/licenses/lgpl.html
 *            GNU Lesser General Public License, version 3 or later
 */
class GetIdentifierNameByJsonDataViewHelper extends AbstractViewHelper
{
    /**
     * formsRepository
     *
     * @var LocalRepository
     */
    protected $localRepository;

    /**
     * Get identifier name from json data
     *        could be a pagename or a string
     *        from given data
     *
     * @param string $data
     * @return string
     */
    public function render($data)
    {
        $dataArray = json_decode($data, true);
        $identifier = '-';
        if (!empty($dataArray['identifier'])) {
            $identifier = $dataArray['identifier'];
            if (is_numeric($dataArray['identifier'])) {
                $row = $this->localRepository->getPropertiesForIdentifier($dataArray['identifier']);
                if (!empty($row['title'])) {
                    $identifier = $row['title'];
                }
            }
        }
        return $identifier;
    }

    /**
     * Init
     *
     * @return void
     */
    public function initialize()
    {
        $this->localRepository = $this->objectManager->get(
            'In2code\\In2publishCore\\Domain\\Repository\\LocalRepository',
            'pages'
        );
    }
}
