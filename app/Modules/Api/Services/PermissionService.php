<?php

namespace App\Modules\Api\Services;

class PermissionService
{
    public static function hasPermission(int $userId, string $permissionKey): bool
    {
        $db = db_connect();

        // Check user-level override first
        $override = $db->table('cd_user_permissions up')
            ->join('cd_permissions p', 'p.id = up.permission_id')
            ->where('up.user_id', $userId)
            ->where('p.permission_key', $permissionKey)
            ->get()
            ->getRow();

        if ($override !== null) {
            return $override->effect === 'allow';
        }

        // Check role-based permissions
        $rolePerm = $db->table('cd_user_roles ur')
            ->join('cd_role_permissions rp', 'rp.role_id = ur.role_id')
            ->join('cd_permissions p', 'p.id = rp.permission_id')
            ->where('ur.user_id', $userId)
            ->where('p.permission_key', $permissionKey)
            ->countAllResults();

        return $rolePerm > 0;
    }

    public static function hasAny(int $userId, array $permissionKeys): bool
    {
        foreach ($permissionKeys as $key) {
            if (self::hasPermission($userId, $key)) return true;
        }
        return false;
    }
}