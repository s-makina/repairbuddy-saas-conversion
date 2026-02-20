<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Role;
use App\Models\User;

echo "--- ROLES CHECK ---\n";
$roles = Role::all();
foreach($roles as $r) {
    echo "ID: {$r->id}, Name: {$r->name}, Tenant: {$r->tenant_id}\n";
}

echo "\n--- USERS role_id CHECK ---\n";
$users = User::all();
foreach($users as $u) {
    echo "ID: {$u->id}, Name: {$u->name}, role_id: {$u->role_id}\n";
}
