<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\AdminTools\Controller;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
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

use In2code\In2publishCore\Communication\RemoteProcedureCall\Letterbox;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class LetterboxController extends ActionController
{
    protected Letterbox $letterbox;

    public function __construct(Letterbox $letterbox)
    {
        $this->letterbox = $letterbox;
    }

    public function indexAction(): ResponseInterface
    {
        $this->view->assign('canFlush', $this->letterbox->hasUnAnsweredEnvelopes());
        return $this->htmlResponse();
    }

    /** @throws StopActionException */
    public function flushEnvelopesAction(): void
    {
        $this->letterbox->removeAnsweredEnvelopes();
        $this->addFlashMessage(
            LocalizationUtility::translate(
                'module.m4.superfluous_envelopes_flushed',
                'in2publish_core'
            )
        );
        $this->redirect('index');
    }
}
