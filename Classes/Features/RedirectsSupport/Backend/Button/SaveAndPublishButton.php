<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport\Backend\Button;

/*
 * Copyright notice
 *
 * (c) 2022 in2code.de and the following authors:
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

use TYPO3\CMS\Backend\Template\Components\AbstractControl;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

use function htmlspecialchars;

class SaveAndPublishButton extends AbstractControl implements ButtonInterface
{
    protected IconFactory $iconFactory;

    public function __construct(IconFactory $iconFactory)
    {
        $this->iconFactory = $iconFactory;
    }

    public function isValid(): bool
    {
        return true;
    }

    public function getType(): string
    {
        return static::class;
    }

    public function __toString(): string
    {
        return $this->render();
    }

    public function render(): string
    {
        $icon = $this->iconFactory->getIcon('actions-document-synchronize', \TYPO3\CMS\Core\Imaging\IconSize::SMALL)->render();
        $label = htmlspecialchars(LocalizationUtility::translate('save_and_publish', 'In2publishCore'));
        return <<<HTML
<button name="_saveandpublish" class="btn btn-default btn-sm " value="1" title="Save" form="EditDocumentController">$icon $label</button>
HTML;
    }
}
