<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        return response()->json([
            'permissions' => Permission::query()->orderBy('name')->get(),
        ]);
    }
}
