<?php

namespace App\Support;

use App\Models\ImpersonationSession;
use App\Models\Tenant;
use App\Models\User;

class ImpersonationContext
{
    private static ?ImpersonationSession $session = null;

    private static ?User $actor = null;

    private static ?User $target = null;

    private static ?Tenant $tenant = null;

    public static function set(ImpersonationSession $session, User $actor, User $target, Tenant $tenant): void
    {
        self::$session = $session;
        self::$actor = $actor;
        self::$target = $target;
        self::$tenant = $tenant;
    }

    public static function clear(): void
    {
        self::$session = null;
        self::$actor = null;
        self::$target = null;
        self::$tenant = null;
    }

    public static function session(): ?ImpersonationSession
    {
        return self::$session;
    }

    public static function actor(): ?User
    {
        return self::$actor;
    }

    public static function target(): ?User
    {
        return self::$target;
    }

    public static function tenant(): ?Tenant
    {
        return self::$tenant;
    }
}
