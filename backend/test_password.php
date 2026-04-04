<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$u = User::find(1);
if (!$u) {
    echo "User 1 not found\n";
    exit;
}

$pass = 'Password123!@#Aa';
$check = Hash::check($pass, $u->password);

echo "Email: {$u->email}\n";
echo "Password Hash: {$u->password}\n";
echo "Hash::check with 'Password123!@#Aa': " . ($check ? "OK" : "FAIL") . "\n";

// Test if it was double hashed
$checkDouble = Hash::check(Hash::make($pass), $u->password);
echo "Hash::check with Hash::make('Password123!@#Aa'): " . ($checkDouble ? "OK (DOUBLE HASHED!)" : "FAIL") . "\n";
