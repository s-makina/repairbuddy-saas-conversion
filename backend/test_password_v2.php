<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

$u = User::where('email', 'superadmin@99smartx.com')->first();
if (!$u) {
    file_put_contents('pass_test.txt', "User not found\n");
    exit;
}

$pass = 'Password123!@#Aa';
$check = Hash::check($pass, $u->password);

$out = "Email: " . $u->email . "\n";
$out .= "Password Hash in DB: " . $u->password . "\n";
$out .= "Hash::check(pass): " . ($check ? "OK" : "FAIL") . "\n";

// Manual check if it matches a cleartext version
$out .= "Hash::make(pass) example: " . Hash::make($pass) . "\n";

file_put_contents('pass_test.txt', $out);
