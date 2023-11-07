<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Controller\Traits;

use In2code\In2publishCore\Component\ConfigContainer\ConfigContainerInjection;
use In2code\In2publishCore\Service\Environment\EnvironmentServiceInjection;
use In2code\In2publishCore\Service\Extension\ExtensionServiceInjection;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;

trait CommonViewVariables
{
    use ConfigContainerInjection;
    use EnvironmentServiceInjection;
    use ExtensionServiceInjection;

    protected function callActionMethod(RequestInterface $request): ResponseInterface
    {
        $this->view->assign('extensionVersion', $this->extensionService->getExtensionVersion('in2publish_core'));
        $this->view->assign('config', $this->configContainer->get());
        $this->view->assign('testStatus', $this->environmentService->getTestStatus());
        return parent::callActionMethod($request);
    }
}
