<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport\ViewHelpers;

/*
 * Copyright notice
 *
 * (c) 2024 in2code.de
 * Daniel Hoffmann <daniel.hoffmann@in2code.de>
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

use In2code\In2publishCore\CommonInjection\TranslationConfigurationProviderInjection;
use In2code\In2publishCore\Features\RedirectsSupport\Domain\Model\SysRedirectDatabaseRecord;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class ShowReasonsButtonViewHelper extends AbstractTagBasedViewHelper
{


    protected $tagName = 'a';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerArgument('redirectRecord', SysRedirectDatabaseRecord::class, '', true);
    }

    public function render() : string
    {
        /**
         * @var $redirectRecord SysRedirectDatabaseRecord
         */
        $redirectRecord = $this->arguments['redirectRecord'];
        $this->tag->addAttribute('href','#');
        $this->tag->setContent($this->renderChildren());
        $modalConfiguration = [
            'settings' => [
                'title' => LocalizationUtility::translate('LLL:EXT:in2publish_core/Resources/Private/Language/locallang_mod5.xlf:modal.reasons.title'),
                'content' => implode("\r\n", $redirectRecord->getReasonsWhyTheRecordIsNotPublishableHumanReadable()),
                'severity' => 1,
            ],
            'buttons' => [
                'abort' => [
                    'text' => 'Abort',
                    'btnClass' => 'btn btn-default',
                    'name' => 'abort',
                    'active' => true,
                ]
            ]
        ];
        $this->tag->addAttribute('data-modal-configuration', json_encode($modalConfiguration));

        return $this->tag->render();
    }
}
