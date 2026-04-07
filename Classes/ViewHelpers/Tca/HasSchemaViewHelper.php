<?php

declare(strict_types=1);

namespace In2code\In2publishCore\ViewHelpers\Tca;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Condition ViewHelper that checks if a TCA schema exists for the given table.
 * Used to guard core:iconForRecord calls for MM tables which have no TCA in TYPO3 v14.
 *
 * Usage: <publish:Tca.HasSchema table="{tableName}"><f:then>...</f:then><f:else>...</f:else></publish:Tca.HasSchema>
 */
class HasSchemaViewHelper extends AbstractConditionViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('table', 'string', 'The table name to check', true);
    }

    public static function verdict(array $arguments, RenderingContextInterface $renderingContext): bool
    {
        return isset($GLOBALS['TCA'][$arguments['table']]);
    }
}
