<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport\Backend\Button;

use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

class BackButton extends LinkButton
{
    public function __construct(IconFactory $iconFactory, UriBuilder $uriBuilder)
    {
        $this->icon = $iconFactory->getIcon('actions-close', \TYPO3\CMS\Core\Imaging\IconSize::SMALL);
        $this->href = $uriBuilder->reset()->uriFor('list');
        $this->title = $GLOBALS['LANG']->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:back');
    }

    public function isValid(): bool
    {
        return true;
    }
}
