<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Middleware;

use In2code\In2publishCore\CommonInjection\SiteFinderInjection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * This middleware works around a bug in TYPO3 Core when using Extbase Backend Modules in file context
 * It can be removed when the issue https://forge.typo3.org/issues/103377 is solved.
 */
class ExtbaseModuleSanitizeParameterMiddleware implements MiddlewareInterface
{
    use SiteFinderInjection;

    protected const SUPPORTED_PATHS = [
        '/typo3/module/in2publish_core/m3',
        '/module/web/In2publishM3',
    ];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isInSupportedPath($request)) {
            return $handler->handle($request);
        }
        $queryParams = $request->getQueryParams();
        if (
            !empty($queryParams['id']) &&
            (int)$queryParams['id'] == $queryParams['id'] &&
            empty($queryParams['combined_identifier'])
        ) {
            $pageUid = 0;
            $sites = $this->siteFinder->getAllSites();
            $firstSite = reset($sites);
            if ($firstSite instanceof Site) {
                $pageUid = $firstSite->getRootPageId();
            }
            $queryParams['combined_identifier'] = $queryParams['id'];
            $queryParams['id'] = $pageUid;
            $request = $request->withQueryParams($queryParams);
        }
        return $handler->handle($request);
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
