<?php

namespace App\Support;

class OrganizationContext
{
    private static ?int $organizationId = null;

    public static function set(?int $organizationId): void
    {
        self::$organizationId = $organizationId;
    }

    public static function id(): ?int
    {
        return self::$organizationId;
    }

    public static function clear(): void
    {
        self::$organizationId = null;
    }
}
