<?php
declare(strict_types=1);
namespace In2code\In2publishCore\ViewHelpers\Tca;

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

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Service\TableConfiguration\LabelService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class GetLabelFieldFromRecordViewHelper
 */
class GetLabelFieldFromRecordViewHelper extends AbstractViewHelper
{
    /**
     * @var LabelService
     */
    protected $labelService;

    /**
     * GetLabelFieldFromRecordViewHelper constructor.
     */
    public function __construct()
    {
        $this->labelService = GeneralUtility::makeInstance(LabelService::class);
    }

    /**
     *
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('record', RecordInterface::class, 'The record whith the property', true);
        $this->registerArgument('stagingLevel', 'string', '"local" (default) or "foreign"', false, 'local');
    }

    /**
     * Get label field from record
     *
     * @return string
     */
    public function render(): string
    {
        return $this->labelService->getLabelField($this->arguments['record'], $this->arguments['stagingLevel']);
    }
}
