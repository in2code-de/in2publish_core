<?php

declare(strict_types=1);

namespace In2code\In2publishCore\ViewHelpers\Security;

use TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

use function hash;

class HashViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('string', 'string', 'The string to hash');
        $this->registerArgument('method', 'string', 'The hashing method', false, 'sha1');
    }

    public function render(): string
    {
        $string = $this->arguments['string'];
        if (null === $string) {
            $string = trim($this->renderChildren());
        }
        $method = $this->arguments['method'];
        return hash($method, $string);
    }

    public function compile(
        $argumentsName,
        $closureName,
        &$initializationPhpCode,
        ViewHelperNode $node,
        TemplateCompiler $compiler
    ): string {
        /** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */
        return "hash({$argumentsName}['method'] ?? 'sha1', {$argumentsName}['string'] ?? trim({$closureName}()))";
    }
}
