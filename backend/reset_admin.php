<?php

/**
 * RepairBuddy Superadmin Reset Utility
 * 
 * Usage: php reset_admin.php [new_password]
 * 
 * This script ensures the Superadmin account is active, has admin privileges,
 * and resets its password to a known value.
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "--------------------------------------------------\n";
echo "RepairBuddy Superadmin Reset Utility\n";
echo "--------------------------------------------------\n\n";

$newPassword = $argv[1] ?? 'Password123!@#Aa';
$email = env('SUPERADMIN_EMAIL', 'superadmin@99smartx.com');

echo "Target Email: $email\n";
echo "Target Password: $newPassword\n\n";

try {
    $user = User::where('email', $email)->first();

    if (!$user) {
        echo "Creating new Superadmin user...\n";
        $user = new User();
        $user->email = $email;
        $user->name = 'Super Admin';
    } else {
        echo "Updating existing Superadmin (ID: {$user->id})...\n";
    }

    $user->password = Hash::make($newPassword);
    $user->is_admin = true;
    $user->status = 'active';
    $user->email_verified_at = now();
    $user->must_change_password = false;
    $user->save();

    echo "SUCCESS: Superadmin account is ready.\n\n";
    echo "Login URL: /superadmin\n";
    echo "Email: $email\n";
    echo "Password: $newPassword\n";
    echo "\n--------------------------------------------------\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
