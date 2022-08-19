<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Permission;

/**
 * @codeCoverageIgnore
 */
trait PermissionServiceInjection
{
    protected PermissionService $permissionService;

    /**
     * @noinspection PhpUnused
     */
    public function injectPermissionService(PermissionService $permissionService): void
    {
        $this->permissionService = $permissionService;
    }
}
