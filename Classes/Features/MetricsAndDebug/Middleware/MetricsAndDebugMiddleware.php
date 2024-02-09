<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\MetricsAndDebug\Middleware;

use In2code\In2publishCore\CommonInjection\ExtensionConfigurationInjection;
use In2code\In2publishCore\Features\MetricsAndDebug\Database\Logging\ContentPublisherSqlLogger;
use In2code\In2publishCore\Features\MetricsAndDebug\Stopwatch\Exception\StopwatchWasNotStartedException;
use In2code\In2publishCore\Features\MetricsAndDebug\Stopwatch\SimpleStopwatchInjection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Utility\DebugUtility;

use function array_column;
use function array_sum;
use function date;
use function str_replace;
use function str_starts_with;
use function uniqid;

class MetricsAndDebugMiddleware implements MiddlewareInterface
{
    use ExtensionConfigurationInjection;
    use SimpleStopwatchInjection;

    protected StreamFactory $streamFactory;

    /**
     * @codeCoverageIgnore
     * @noinspection PhpUnused
     */
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
                unset($query['caller']);
                $queriesByCaller[$caller][] = $query;
            }
            uksort($queriesByCaller, static fn ($a, $b) => count($queriesByCaller[$b]) - count($queriesByCaller[$a]));

            foreach ($queriesByCaller as $caller => $callerQueries) {
                $times = array_column($callerQueries, 'executionNS');
                $durationNS = array_sum($times);
                $durationMs = (int)($durationNS / 1e+6);
                $durationS = (int)($durationMs / 1000);
                $remainingMs = $durationMs - $durationS * 1000;
                $duration = date('i:s', (int)$durationS) . '.' . $remainingMs;
                unset($queriesByCaller[$caller]);
                $queriesByCaller["$caller ($duration)"] = $callerQueries;
            }
            if (!empty($queries)) {
                /** @noinspection PhpRedundantOptionalArgumentInspection */
                $requestGroup = uniqid('request_', false);
                DebugUtility::debug($queries, 'Content Publisher Queries', $requestGroup);
                DebugUtility::debug($queriesByCaller, 'Queries By Caller', $requestGroup);
                DebugUtility::debug(array_sum(array_column($queries, 'executionNS')), 'Timing', $requestGroup);
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
