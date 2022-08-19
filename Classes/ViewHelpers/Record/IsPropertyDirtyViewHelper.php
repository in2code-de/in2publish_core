<?php

declare(strict_types=1);

namespace In2code\In2publishCore\ViewHelpers\Record;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
 * Andreas Nedbal <andreas.nedbal@in2code.de>
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

use In2code\In2publishCore\Component\Core\Record\Model\Record;
use TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

use function in_array;

class IsPropertyDirtyViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('record', Record::class, 'record which contains the property', true);
        $this->registerArgument('property', 'string', 'name of the property that should be checked', true);
    }

    public function render(): bool
    {
        /** @var Record $record */
        $record = $this->arguments['record'];
        return in_array($this->arguments['property'], $record->getChangedProps(), true);
    }

    public function compile(
        $argumentsName,
        $closureName,
        &$initializationPhpCode,
        ViewHelperNode $node,
        TemplateCompiler $compiler
    ) {
        /** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */
        return "in_array({$argumentsName}['property'], {$argumentsName}['record']->getChangedProps(), true)";
    }
}
