<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "--- TECHNICIANS CHECK ---\n";
$techsByRoleField = User::where('role', 'technician')->get();
echo "Users with role='technician': " . $techsByRoleField->count() . "\n";
foreach($techsByRoleField as $u) echo " - ID: {$u->id}, Name: {$u->name}, Status: {$u->status}\n";

$techsByRoleClass = User::whereHas('roles', fn($q) => $q->where('name', 'Technician'))->get();
echo "Users with Role name 'Technician': " . $techsByRoleClass->count() . "\n";
foreach($techsByRoleClass as $u) echo " - ID: {$u->id}, Name: {$u->name}, Status: {$u->status}\n";

$allUsers = User::all();
echo "Scanning all users for status and is_admin:\n";
foreach($allUsers as $u) {
    $roles = $u->roles->pluck('name')->toArray();
    echo "ID: {$u->id}, Name: {$u->name}, Status: {$u->status}, Admin: " . ($u->is_admin ? 'Yes' : 'No') . ", RoleField: {$u->role}, Roles: " . implode(',', $roles) . "\n";
}
