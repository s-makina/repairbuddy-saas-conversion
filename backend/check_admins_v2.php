<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

foreach(User::where('is_admin', true)->get() as $u) {
    file_put_contents('admin_details.txt', "ID: {$u->id}\nName: {$u->name}\nEmail: {$u->email}\nStatus: {$u->status}\nIsAdmin: " . ($u->is_admin ? 'Yes' : 'No') . "\n", FILE_APPEND);
}
