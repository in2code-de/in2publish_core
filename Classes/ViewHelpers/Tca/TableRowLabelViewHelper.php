<?php

declare(strict_types=1);

namespace In2code\In2publishCore\ViewHelpers\Tca;

use Closure;
use In2code\In2publishCore\Service\Configuration\TcaService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class TableRowLabelViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    public function initializeArguments(): void
    {
        $this->registerArgument('table', 'string', 'The row\'s table', true);
        $this->registerArgument('row', 'array', 'The row itself', true);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function renderStatic(
        array $arguments,
        Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $tcaService = GeneralUtility::makeInstance(TcaService::class);

        $table = (string)$arguments['table'];
        $row = (array)$arguments['row'];

        return $tcaService->getRecordLabel($row, $table);
    }
}
