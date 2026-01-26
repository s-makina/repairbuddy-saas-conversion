<?php

namespace App\Support;

use App\Models\Branch;

class BranchContext
{
    private static ?Branch $branch = null;

    public static function set(?Branch $branch): void
    {
        self::$branch = $branch;
    }

    public static function branch(): ?Branch
    {
        return self::$branch;
    }

    public static function branchId(): ?int
    {
        return self::$branch?->id;
    }
}
