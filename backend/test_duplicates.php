<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

$duplicates = DB::table('users')
    ->select('email', DB::raw('count(*) as count'))
    ->groupBy('email')
    ->having('count', '>', 1)
    ->get();

$out = "Duplicate emails count: " . $duplicates->count() . "\n";
foreach($duplicates as $d) {
    $out .= "Email: {$d->email}, Count: {$d->count}\n";
    $users = User::where('email', $d->email)->get();
    foreach($users as $u) {
        $out .= " - ID: {$u->id}, IsAdmin: " . ($u->is_admin ? 'Yes' : 'No') . ", Status: {$u->status}\n";
    }
}

file_put_contents('duplicates_test.txt', $out);
