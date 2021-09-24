<?php

declare(strict_types=1);

namespace In2code\In2publishCore\ViewHelpers\Be;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de and the following authors:
 * Alex Kellner <alexander.kellner@in2code.de>,
 * Oliver Eglseder <oliver.eglseder@in2code.de>
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

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class GetClassNameOnActionViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('actionName', 'string', 'action name to compare with current action', true);
        $this->registerArgument('className', 'string', 'class name if action fits', false, ' btn-primary');
        $this->registerArgument('fallbackClassName', 'string', 'fallback class name', false, ' btn-default');
    }

    /**
     * Return className if actionName fits to current action
     */
    public function render(): string
    {
        if ($this->getCurrentActionName() === $this->arguments['actionName']) {
            return $this->arguments['className'];
        }
        return $this->arguments['fallbackClassName'];
    }

    protected function getCurrentActionName(): string
    {
        return $this->renderingContext->getControllerAction();
    }
}
