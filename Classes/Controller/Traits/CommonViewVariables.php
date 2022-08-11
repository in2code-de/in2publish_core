<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Controller\Traits;

use In2code\In2publishCore\Component\ConfigContainer\ConfigContainerInjection;
use In2code\In2publishCore\Service\Environment\EnvironmentServiceInjection;
use In2code\In2publishCore\Utility\ExtensionUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

trait CommonViewVariables
{
    use ConfigContainerInjection;
    use EnvironmentServiceInjection;

    protected function initializeView(ViewInterface $view): void
    {
        $view->assign('extensionVersion', ExtensionUtility::getExtensionVersion('in2publish_core'));
        $view->assign('config', $this->configContainer->get());
        $view->assign('testStatus', $this->environmentService->getTestStatus());
    }
}
