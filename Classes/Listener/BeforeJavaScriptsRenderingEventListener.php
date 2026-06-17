<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Listener;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Page\Event\BeforeJavaScriptsRenderingEvent;

class BeforeJavaScriptsRenderingEventListener
{
    #[AsEventListener(
        identifier: 'in2publish-core-add-backend-javascript'
    )]
    public function __invoke(BeforeJavaScriptsRenderingEvent $event): void
    {
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()) {
            $assetCollector = $event->getAssetCollector();
            $assetCollector->addJavaScript(
                'in2publish-core-global-backend-functions',
                'EXT:in2publish_core/Resources/Public/JavaScript/backendGlobal.js',
                ['type' => 'module']
            );
        }
    }
}
