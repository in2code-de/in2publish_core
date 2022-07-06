<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Controller\Traits;

use function is_bool;

trait ControllerFilterStatus
{
    protected function toggleFilterStatus(string $filterName, string $status): array
    {
        $currentStatus = $GLOBALS['BE_USER']->getSessionData($filterName . $status);
        if (!is_bool($currentStatus)) {
            $currentStatus = false;
        }
        $GLOBALS['BE_USER']->setAndSaveSessionData($filterName . $status, !$currentStatus);
        return [
            'name' => $filterName,
            'status' => $status,
            'oldStatus' => $currentStatus,
            'newStatus' => $GLOBALS['BE_USER']->getSessionData($filterName . $status),
        ];
    }
}
