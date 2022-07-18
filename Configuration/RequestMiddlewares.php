<?php

declare(strict_types=1);

use In2code\In2publishCore\Features\MetricsAndDebug\Middleware\MetricsAndDebugMiddleware;

return [
    'backend' => [
        'in2code/in2publish/debugging' => [
            'target' => MetricsAndDebugMiddleware::class,
            'after' => 'typo3/cms-core/response-propagation',
        ],
    ],
];
