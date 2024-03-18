<?php

declare(strict_types=1);

use In2code\In2publishCore\Features\MetricsAndDebug\Middleware\MetricsAndDebugMiddleware;
use In2code\In2publishCore\Middleware\ExtbaseModuleSanitizeParameterMiddleware;
use In2code\In2publishCore\Middleware\InjectLoadingOverlayMiddleware;
use In2code\In2publishCore\Middleware\MakeRequestAvaiableMiddleware;

return [
    'frontend' => [
        'in2code/in2publish_core/make-request-avaiable-middleware' => [
            'target' => MakeRequestAvaiableMiddleware::class,
            'before' => [
                'typo3/cms-core/normalized-params-attribute',
            ],
            'after' => [
                'typo3/cms-core/verify-host-header',
            ],
        ]
    ],
    'backend' => [
        'in2code/in2publish_core/debugging' => [
            'target' => MetricsAndDebugMiddleware::class,
            'after' => [
                'typo3/cms-core/response-propagation',
            ],
        ],
        'in2code/in2publish_core/inject-loading-overlay' => [
            'target' => InjectLoadingOverlayMiddleware::class,
            'after' => [
                'typo3/cms-core/response-propagation',
            ],
        ],
        'in2code/in2publish_core/extbase-module-sanitize-parameter-middleware' => [
            'target' => ExtbaseModuleSanitizeParameterMiddleware::class,
            'after' => [
                'typo3/cms-core/response-propagation',
            ],
        ],
    ],
];
