<?php

namespace App\Modules\Api\Libraries;

class TenantContext
{
    private static ?int $tenantId = null;
    private static ?int $branchId = null;
    private static ?int $userId = null;

    public static function set(int $tenantId, ?int $branchId = null, ?int $userId = null): void
    {
        self::$tenantId = $tenantId;
        self::$branchId = $branchId;
        self::$userId = $userId;
    }

    public static function getTenantId(): ?int
    {
        return self::$tenantId;
    }

    public static function getBranchId(): ?int
    {
        return self::$branchId;
    }

    public static function getUserId(): ?int
    {
        return self::$userId;
    }

    public static function isSuperAdmin(): bool
    {
        return self::$tenantId === null && self::$userId !== null;
    }

    public static function reset(): void
    {
        self::$tenantId = null;
        self::$branchId = null;
        self::$userId = null;
    }
}