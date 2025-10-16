<?php
declare(strict_types=1);

namespace In2code\In2publishCore\Listener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Page\Event\BeforeJavaScriptsRenderingEvent;

class BeforeJavaScriptsRenderingEventListener
{
    #[AsEventListener(
        identifier: 'in2publish-core-add-backend-javascript'
    )]
    public function __invoke(BeforeJavaScriptsRenderingEvent $event): void
    {
        $assetCollector = $event->getAssetCollector();
        $assetCollector->addJavaScript(
            'in2publish-core-global-backend-functions',
            'EXT:in2publish_core/Resources/Public/JavaScript/backendGlobal.js',
            ['type' => 'module']
        );
    }
}
