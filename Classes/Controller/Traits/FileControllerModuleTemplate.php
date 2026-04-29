<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Controller\Traits;

use In2code\In2publishCore\Backend\Button\ModuleShortcutButton;
use In2code\In2publishCore\Backend\Template\ModuleTemplateFactory;
use In2code\In2publishCore\Event\ModuleTemplateWasPreparedForRendering;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;

use function str_replace;
use function strtolower;

/**
 *  WORKAROUND:
 *
 *  this ModuleTemplateFactory is only used in the PublishFiles Backend Module (FileController).
 *  This Factory can be removed if the core supports a way to override templates for backend modules which use
 *  the id parameter to store the current selected folder (like filelist)
 *
 * override the "misuse" of the "id" argument (see: typo3/cms-backend/Classes/View/BackendViewFactory.php:70).
 * Otherwise, a template override is not possible because the PageTs is not loaded.
 *
 * The "filelist" and therefore the "publish files" modules use the "id" parameter to specify the currently selected
 *  folder instead of the selected page.
 *
 * @see: https://projekte.in2code.de/issues/76154
 * @property Request $request
 * @property EventDispatcherInterface $eventDispatcher
 * @property string $actionMethodName
 */
trait FileControllerModuleTemplate
{
    protected ModuleTemplateFactory $moduleTemplateFactory;

    protected ModuleTemplate $moduleTemplate;

    /**
     * @noinspection PhpUnused
     */
    public function injectModuleTemplateFactory(ModuleTemplateFactory $moduleTemplateFactory): void
    {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function processRequest(RequestInterface $request): ResponseInterface
    {
        if ($request instanceof ServerRequestInterface) {
            $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
            $this->moduleTemplate->setModuleId(strtolower(str_replace('\\', '_', static::class)));
        }
        return parent::processRequest($request);
    }

    protected function htmlResponse(?string $html = null): ResponseInterface
    {
        return $this->render();
    }

    protected function render(): ResponseInterface
    {
        $docHeader = $this->moduleTemplate->getDocHeaderComponent();
        $buttonBar = $docHeader->getButtonBar();

        $moduleShortcutButton = GeneralUtility::makeInstance(ModuleShortcutButton::class);
        $moduleShortcutButton->setRequest($this->request);
        $buttonBar->addButton($moduleShortcutButton);

        $event = new ModuleTemplateWasPreparedForRendering(
            $this->moduleTemplate,
            static::class,
            $this->actionMethodName
        );
        $this->eventDispatcher->dispatch($event);

        return $this->moduleTemplate->renderResponse($this->request->getControllerName() . '/' . ucfirst($this->request->getControllerActionName()));
    }
}
