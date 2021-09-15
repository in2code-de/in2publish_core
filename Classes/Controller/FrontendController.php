<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Controller;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
 * Alex Kellner <alexander.kellner@in2code.de>,
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use In2code\In2publishCore\Utility\BackendUtility;

class FrontendController extends AbstractController
{
    /**
     * Preview action for vertical or horizontal view
     *
     * @param int $identifier
     *
     * @return void
     */
    public function previewAction(int $identifier = 1)
    {
        $this->view->assign(
            'local_preview',
            (string)BackendUtility::buildPreviewUri('pages', $identifier, 'local')
        );
        $this->view->assign(
            'foreign_preview',
            (string)BackendUtility::buildPreviewUri('pages', $identifier, 'foreign')
        );
    }
}
