<?php

namespace In2code\In2publishCore\ViewHelpers\Math;

use TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

use function sprintf;

class SubtractViewHelper extends AbstractViewHelper
{
    protected $escapeChildren = false;

    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('value', 'int', 'The value increment');
        $this->registerArgument('increment', 'int', 'The number to add');
    }

    public function render(): int
    {
        $value = $this->arguments['value'];
        $increment = $this->arguments['increment'];

        return $value - $increment;
    }

    public function compile(
        $argumentsName,
        $closureName,
        &$initializationPhpCode,
        ViewHelperNode $node,
        TemplateCompiler $compiler
    ): string {
        return sprintf('(int)(%1$s[\'value\'] - %1$s[\'increment\'])', $argumentsName);
    }

}
