<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $has = Illuminate\Support\Facades\Schema::hasTable('settings');
    echo 'Settings table exists: ' . ($has ? 'YES' : 'NO') . PHP_EOL;

    if ($has) {
        $count = Illuminate\Support\Facades\DB::table('settings')->count();
        echo 'Total settings rows: ' . $count . PHP_EOL;
        $general = Illuminate\Support\Facades\DB::table('settings')
            ->where('key', 'like', 'general_%')
            ->get();
        echo 'General settings: ' . $general->count() . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    echo 'File: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
}
