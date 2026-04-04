<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$admins = User::where('is_admin', true)->get();
echo "Admins count: " . $admins->count() . "\n";
foreach($admins as $u) {
    echo "ID: {$u->id}, Name: {$u->name}, Email: {$u->email}, Status: {$u->status}, IsAdmin: " . ($u->is_admin ? 'Yes' : 'No') . "\n";
}
