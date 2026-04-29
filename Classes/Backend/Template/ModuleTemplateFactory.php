<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Backend\Template;

use In2code\In2publishCore\Backend\View\BackendViewFactory;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;

/**
 * WORKAROUND!
 *
 * this ModuleTemplateFactory is only used in the PublishFiles Backend Module (FileController).
 * This Factory can be removed if the core supports a way to override templates for backend modules which use
 * the id parameter to store the current selected folder (like filelist)
 */
#[Autoconfigure(public: true, shared: false)]
final class ModuleTemplateFactory
{
    public function __construct(
        protected readonly PageRenderer $pageRenderer,
        protected readonly IconFactory $iconFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleProvider $moduleProvider,
        protected readonly FlashMessageService $flashMessageService,
        protected readonly ExtensionConfiguration $extensionConfiguration,
        protected readonly BackendViewFactory $viewFactory,
        protected readonly ComponentFactory $componentFactory,
    ) {
    }

    public function create(ServerRequestInterface $request): ModuleTemplate
    {
        return new ModuleTemplate(
            $this->pageRenderer,
            $this->iconFactory,
            $this->uriBuilder,
            $this->moduleProvider,
            $this->flashMessageService,
            $this->extensionConfiguration,
            $this->viewFactory->create($request),
            $this->componentFactory,
            $request
        );
    }
}
