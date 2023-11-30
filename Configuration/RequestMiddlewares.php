<?php

declare(strict_types=1);

use In2code\In2publishCore\Features\MetricsAndDebug\Middleware\MetricsAndDebugMiddleware;
use In2code\In2publishCore\Middleware\InjectLoadingOverlayMiddleware;

return [
    'backend' => [
        'in2code/in2publish_core/debugging' => [
            'target' => MetricsAndDebugMiddleware::class,
            'after' => 'typo3/cms-core/response-propagation',
        ],
        'in2code/in2publish_core/inject-loading-overlay' => [
            'target' => InjectLoadingOverlayMiddleware::class,
            'after' => 'typo3/cms-core/response-propagation',
        ],
    ],
];
