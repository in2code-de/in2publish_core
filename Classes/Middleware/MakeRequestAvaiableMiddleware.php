<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MakeRequestAvaiableMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $GLOBALS['IN2PUBLISH_IS_FRONTEND'] = true;
        return $handler->handle($request);
    }
}
