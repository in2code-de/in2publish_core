<?php

declare(strict_types=1);

namespace In2code\In2publishCore\ViewHelpers\Format;

use Closure;
use TYPO3\CMS\Core\Utility\DiffUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class DiffViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    protected $escapeOutput = false;
    private const ARG_OLD = 'old';
    private const ARG_NEW = 'new';

    public function initializeArguments(): void
    {
        $this->registerArgument(self::ARG_OLD, 'string', 'The old string', true);
        $this->registerArgument(self::ARG_NEW, 'string', 'The new string', true);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function renderStatic(
        array $arguments,
        Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        $diffUtility = GeneralUtility::makeInstance(DiffUtility::class);
        $diffUtility->stripTags = false;
        return $diffUtility->makeDiffDisplay(
            (string)$arguments[self::ARG_OLD],
            (string)$arguments[self::ARG_NEW]
        );
    }
}
