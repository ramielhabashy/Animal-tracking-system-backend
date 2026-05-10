<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Setting UTF-8 charset...\n";
DB::statement("SET NAMES 'utf8mb4'");
DB::statement("SET CHARACTER SET utf8mb4");

$languages = [
    'en' => ['name' => 'English', 'native_name' => 'English', 'direction' => 'ltr'],
    'ar' => ['name' => 'Arabic', 'native_name' => 'العربية', 'direction' => 'rtl'],
    'ur' => ['name' => 'Urdu', 'native_name' => 'اردو', 'direction' => 'rtl'],
    'eu' => ['name' => 'Basque', 'native_name' => 'Euskara', 'direction' => 'ltr'],
];

echo "Updating languages...\n";
foreach ($languages as $code => $data) {
    $existing = DB::table('languages')->where('code', $code)->first();
    if ($existing) {
        DB::table('languages')->where('code', $code)->update($data);
        echo "  Updated {$code}: {$data['native_name']}\n";
    } else {
        DB::table('languages')->insert(array_merge(['code' => $code], $data));
        echo "  Inserted {$code}: {$data['native_name']}\n";
    }
}

echo "\nVerification (with hex dump):\n";
$all = DB::table('languages')->orderBy('code')->get();
foreach ($all as $l) {
    $hex = bin2hex($l->native_name);
    echo "  {$l->code}: {$l->native_name} (direction: {$l->direction}) [hex: {$hex}]\n";
}

echo "\nDone!\n";
