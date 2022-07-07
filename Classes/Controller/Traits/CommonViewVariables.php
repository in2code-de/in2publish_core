<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Controller\Traits;

use In2code\In2publishCore\Component\ConfigContainer\ConfigContainer;
use In2code\In2publishCore\Domain\Service\ExecutionTimeService;
use In2code\In2publishCore\Service\Environment\EnvironmentService;
use In2code\In2publishCore\Utility\ExtensionUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

trait CommonViewVariables
{
    protected ConfigContainer $configContainer;
    protected ExecutionTimeService $executionTimeService;
    protected EnvironmentService $environmentService;

    public function injectConfigContainer(ConfigContainer $configContainer): void
    {
        $this->configContainer = $configContainer;
    }

    public function injectExecutionTimeService(ExecutionTimeService $executionTimeService): void
    {
        $executionTimeService->start();
        $this->executionTimeService = $executionTimeService;
    }

    public function injectEnvironmentService(EnvironmentService $environmentService): void
    {
        $this->environmentService = $environmentService;
    }

    protected function initializeView(ViewInterface $view): void
    {
        $view->assign('extensionVersion', ExtensionUtility::getExtensionVersion('in2publish_core'));
        $view->assign('config', $this->configContainer->get());
        $view->assign('executionTimeService', $this->executionTimeService);
        $view->assign('testStatus', $this->environmentService->getTestStatus());
    }
}
