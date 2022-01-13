<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Middleware;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use In2code\In2publishCore\Event\ExtTablesPostProcessingEvent;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Routing\Exception\MethodNotAllowedException;
use TYPO3\CMS\Backend\Routing\Exception\ResourceNotFoundException;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This XCLASS dispatches an event after Bootstrap::loadExtTables() to truly replace ExtTablesPostProcessingHooks.
 *
 * XCLASS original \TYPO3\CMS\Backend\Middleware\BackendRouteInitialization
 * Required until the patch got merged and released.
 *
 * Issue: https://forge.typo3.org/issues/95962
 * Patch: https://review.typo3.org/c/Packages/TYPO3.CMS/+/72160
 *
 * Have a look at the event ExtTablesPostProcessingEvent for more information.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) Can't reduce. It is already as small as possible. And an XCLASS...
 */
class BackendRouteInitialization implements MiddlewareInterface
{
    protected Router $router;

    private EventDispatcher $eventDispatcher;

    public function __construct(Router $router, EventDispatcher $eventDispatcher)
    {
        $this->router = $router;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Resolve the &route (or &M) GET/POST parameter, and also resolves a Route object
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Backend Routes from Configuration/Backend/{,Ajax}Routes.php will be implicitly loaded thanks to DI.
        // Load ext_tables.php files to add routes from ExtensionManagementUtility::addModule() calls.
        Bootstrap::loadExtTables();
        $this->eventDispatcher->dispatch(new ExtTablesPostProcessingEvent());

        try {
            $route = $this->router->matchRequest($request);
            $request = $request->withAttribute('route', $route);
            $request = $request->withAttribute('target', $route->getOption('target'));
            // add the GET parameter "route" for backwards-compatibility
            $queryParams = $request->getQueryParams();
            $queryParams['route'] = $route->getPath();
            $request = $request->withQueryParams($queryParams);
        } catch (MethodNotAllowedException $e) {
            return new Response(null, 405);
        } catch (ResourceNotFoundException $e) {
            // Route not found in system
            $uri = GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('login');
            return new RedirectResponse($uri);
        }

        return $handler->handle($request);
    }
}
