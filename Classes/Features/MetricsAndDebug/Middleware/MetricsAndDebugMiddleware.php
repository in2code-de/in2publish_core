<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\MetricsAndDebug\Middleware;

use In2code\In2publishCore\Features\MetricsAndDebug\Database\Logging\ContentPublisherSqlLogger;
use In2code\In2publishCore\Features\MetricsAndDebug\Stopwatch\Exception\StopwatchWasNotStartedException;
use In2code\In2publishCore\Features\MetricsAndDebug\Stopwatch\SimpleStopwatch;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Utility\DebugUtility;

use function str_replace;
use function str_starts_with;

class MetricsAndDebugMiddleware implements MiddlewareInterface
{
    protected ExtensionConfiguration $extensionConfiguration;
    protected SimpleStopwatch $simpleStopwatch;
    protected StreamFactory $streamFactory;

    public function injectExtensionConfiguration(ExtensionConfiguration $extensionConfiguration): void
    {
        $this->extensionConfiguration = $extensionConfiguration;
    }

    public function injectSimpleStopwatch(SimpleStopwatch $simpleStopwatch): void
    {
        $this->simpleStopwatch = $simpleStopwatch;
    }

    public function injectStreamFactory(StreamFactory $streamFactory): void
    {
        $this->streamFactory = $streamFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->simpleStopwatch->start();

        $response = $handler->handle($request);

        // Early return for ajax request e.g. pagetree or dashboard widgets.
        $contentType = $response->getHeaderLine('Content-Type');
        $route = $request->getQueryParams()['route'] ?? '';
        if (
            !str_starts_with($contentType, 'text/html;')
            || str_starts_with($route, '/ajax/')
        ) {
            return $response;
        }

        $body = $response->getBody();
        $body->rewind();
        $contents = $body->getContents();
        // Safeguard against modification of json/xml or other content.
        if (!str_starts_with($contents, '<!DOCTYPE html>')) {
            return $response;
        }

        $this->debugSqlQueries();
        return $this->replaceExecutionTimeMarker($contents, $response);
    }

    /**
     * @noinspection ForgottenDebugOutputInspection
     */
    protected function debugSqlQueries(): void
    {
        if ($this->extensionConfiguration->get('in2publish_core', 'debugQueries')) {
            $queries = ContentPublisherSqlLogger::getQueries();
            $queriesByCaller = [];
            foreach ($queries as $query) {
                $caller = $query['caller'];
                $queriesByCaller[$caller][] = $query;
            }
            if (!empty($queries)) {
                DebugUtility::debug($queries, 'Content Publisher Queries');
                DebugUtility::debug($queriesByCaller, 'Queries By Caller');
            }
        }
    }

    protected function replaceExecutionTimeMarker(string $contents, ResponseInterface $response): ResponseInterface
    {
        try {
            $executionTime = $this->simpleStopwatch->getTime();
        } catch (StopwatchWasNotStartedException $e) {
            $executionTime = 'Timer was never started';
        }
        $count = 0;
        $contents = str_replace('###IN2CODE_IN2PUBLISH_EXECUTION_TIME###', $executionTime, $contents, $count);
        if ($count === 0) {
            return $response;
        }
        $response = $response->withBody(new Stream('php://temp', 'r+'));
        $response->getBody()->write($contents);
        $newBody = $this->streamFactory->createStream($contents);
        return $response->withBody($newBody);
    }
}
