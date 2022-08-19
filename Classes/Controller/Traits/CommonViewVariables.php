<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Controller\Traits;

use In2code\In2publishCore\Component\ConfigContainer\ConfigContainerInjection;
use In2code\In2publishCore\Service\Environment\EnvironmentServiceInjection;
use In2code\In2publishCore\Service\Extension\ExtensionServiceInjection;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

trait CommonViewVariables
{
    use ConfigContainerInjection;
    use EnvironmentServiceInjection;
    use ExtensionServiceInjection;

    protected function initializeView(ViewInterface $view): void
    {
        $view->assign('extensionVersion', $this->extensionService->getExtensionVersion('in2publish_core'));
        $view->assign('config', $this->configContainer->get());
        $view->assign('testStatus', $this->environmentService->getTestStatus());
    }
}
