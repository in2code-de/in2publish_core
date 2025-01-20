<?php

declare(strict_types=1);

namespace In2code\In2publishCore\ViewHelpers\Format;

use TYPO3\CMS\Core\Utility\DiffUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class DiffViewHelper extends AbstractViewHelper
{
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
    public function render(): string
    {
        $diffUtility = GeneralUtility::makeInstance(DiffUtility::class);
        $diffUtility->stripTags = false;
        return $diffUtility->makeDiffDisplay(
            (string)$this->arguments[self::ARG_OLD],
            (string)$this->arguments[self::ARG_NEW],
        );
    }
}
