<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Animal;

$user = User::where('email', 'zeno@oasis.com')->first();

if (!$user) {
    echo "zeno@oasis.com NOT FOUND" . PHP_EOL;
    exit;
}

echo "=== ZENO ===" . PHP_EOL;
echo "ID: " . $user->id . PHP_EOL;
echo "Name: " . $user->name . PHP_EOL;
echo "Email: " . $user->email . PHP_EOL;
echo "Managed by: " . ($user->managed_by ?? 'NULL') . PHP_EOL;
echo "Managed by type: " . gettype($user->managed_by) . PHP_EOL;
echo "Has role 'Doctor': " . ($user->hasRole('Doctor') ? 'TRUE' : 'FALSE') . PHP_EOL;
echo "Has role 'Owner': " . ($user->hasRole('Owner') ? 'TRUE' : 'FALSE') . PHP_EOL;
echo "Primary role: " . $user->getPrimaryRoleName() . PHP_EOL;
echo "All roles: " . implode(', ', $user->getRoleNames()->toArray()) . PHP_EOL;
echo PHP_EOL;

echo "=== KHALID ===" . PHP_EOL;
$khalid = User::where('email', 'khalid@oasis.com')->first();
if ($khalid) {
    echo "ID: " . $khalid->id . PHP_EOL;
    echo "Khalid animals count: " . Animal::where('owner_id', $khalid->id)->count() . PHP_EOL;
}
echo PHP_EOL;

echo "=== SIMULATED QUERY ===" . PHP_EOL;
if ($user->hasRole('Doctor')) {
    if ($user->managed_by) {
        $ownerId = (int) $user->managed_by;
        echo "Doctor filtering by owner_id = $ownerId" . PHP_EOL;
        $count = Animal::where('owner_id', $ownerId)->count();
        echo "Animals found: $count" . PHP_EOL;
        $animals = Animal::where('owner_id', $ownerId)->get(['id', 'name', 'owner_id']);
        foreach ($animals as $a) {
            echo "  - Animal ID={$a->id}, name='{$a->name}', owner_id={$a->owner_id}" . PHP_EOL;
        }
    } else {
        echo "Doctor has no managed_by, returning empty" . PHP_EOL;
    }
}
