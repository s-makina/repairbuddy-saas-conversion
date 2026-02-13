<?php

namespace App\Http\Controllers\Web;

class BranchContext
{
    public static function set($branch): void
    {
        \App\Support\BranchContext::set($branch);
    }

    public static function branch()
    {
        return \App\Support\BranchContext::branch();
    }

    public static function branchId(): ?int
    {
        return \App\Support\BranchContext::branchId();
    }
}
