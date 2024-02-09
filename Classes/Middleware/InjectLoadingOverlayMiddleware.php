<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function str_contains;
use function str_starts_with;
use function strpos;
use function strtolower;
use function substr;

class InjectLoadingOverlayMiddleware implements MiddlewareInterface
{
    protected const CODE = <<<HTML
<div class="in2publish-loading-overlay in2publish-loading-overlay--hidden">
	<div class="in2publish-loading-overlay--spinner">
		<div class="rect1"></div>
		<div class="rect2"></div>
		<div class="rect3"></div>
		<div class="rect4"></div>
		<div class="rect5"></div>
	</div>
</div>
HTML;
    protected const SUPPORTED_PATHS = [
        '/typo3/module/web/',
        '/typo3/module/file/in2publish',
        '/typo3/module/site/in2publish',
        '/typo3/module/tools/in2publish',
        '/typo3/module/in2publish',
        '/module/web/In2publishM2',
    ];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isInSupportedPath($request)) {
            return $handler->handle($request);
        }

        /** @var PageRenderer $pageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addCssFile(
            'EXT:in2publish_core/Resources/Public/Css/LoadingOverlay.css',
            'stylesheet',
            'all',
            '',
            false,
        );
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/In2publishCore/LoadingOverlay');
        $pageRenderer->addInlineLanguageLabelFile('EXT:in2publish_core/Resources/Private/Language/locallang_js.xlf');

        $response = $handler->handle($request);

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $body = $response->getBody();
            if ($body->isSeekable()) {
                $body->rewind();
            }
            $contents = $body->getContents();
            if (!str_contains($contents, '<body>')) {
                return $response;
            }

            $offset = strpos($contents, '<body>') + 6;
            $contents = substr($contents, 0, $offset) . self::CODE . substr($contents, $offset);

            $streamFactory = GeneralUtility::makeInstance(StreamFactory::class);
            $newBody = $streamFactory->createStream($contents);
            $response = $response->withBody($newBody);
        }

        return $response;
    }

    protected function isInSupportedPath(ServerRequestInterface $request): bool
    {
        $requestPath = strtolower($request->getUri()->getPath());
        foreach (self::SUPPORTED_PATHS as $path) {
            if (str_starts_with($requestPath, $path) || str_starts_with($_GET['route'] ?? '', $path)) {
                return true;
            }
        }
        return false;
    }
}
