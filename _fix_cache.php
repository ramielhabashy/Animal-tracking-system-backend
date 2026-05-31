<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Clear Spatie permission cache
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
echo 'Spatie permission cache cleared.' . PHP_EOL;

// Also clear Laravel cache
\Illuminate\Support\Facades\Cache::flush();
echo 'Laravel cache flushed.' . PHP_EOL;

// Verify Doctor's permissions
$user = App\Models\User::where('email', 'zeno@oasis.com')->first();
echo 'Doctor ' . $user->email . ' can animal_create: ' . ($user->can('animal_create') ? 'YES' : 'NO') . PHP_EOL;
