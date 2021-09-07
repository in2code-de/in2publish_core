<?php

declare(strict_types=1);

use In2code\In2publishCore\Features\ContextMenuPublishEntry\Controller\PublishPageAjaxController;

return [
    'in2publishcore_contextmenupublishentry_publish' => [
        'path' => '/tx_in2publishcore/contextmenupublishentry/publish',
        'target' => PublishPageAjaxController::class . '::publishPage'
    ]
];
