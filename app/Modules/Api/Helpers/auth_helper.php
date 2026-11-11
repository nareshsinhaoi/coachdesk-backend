<?php

use App\Modules\Api\Services\PermissionService;

if (! function_exists('can')) {
    function can(string $permissionKey): bool
    {
        $user = service('request')->user ?? null;
        if ($user === null) return false;
        if ($user->tenant_id === null) return true; // super admin
        return PermissionService::hasPermission((int) $user->id, $permissionKey);
    }
}