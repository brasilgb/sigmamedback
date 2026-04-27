<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;

class TenantContext
{
    protected static ?Tenant $tenant = null;

    public static function set(Tenant $tenant): void
    {
        self::$tenant = $tenant;
    }

    public static function current(): ?Tenant
    {
        return self::$tenant;
    }

    public static function clear(): void
    {
        self::$tenant = null;
    }
}
