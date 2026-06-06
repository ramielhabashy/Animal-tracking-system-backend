<?php
echo "Testing SMTP connectivity from container...\n\n";

// Quick connectivity test only
$tests = [
    ['host71.registrar-servers.com', 465, 'ssl'],
    ['host71.registrar-servers.com', 587, 'tls'],
    ['era-solutions.com', 587, 'tls'],
    ['era-solutions.com', 465, 'ssl'],
];

foreach ($tests as $i => $t) {
    $host = $t[0];
    $port = $t[1];
    $type = $t[2];
    echo ($i+1) . ") $host:$port ($type)... ";
    $start = microtime(true);
    $fp = @fsockopen(($type === 'ssl' ? 'ssl://' : '') . $host, $port, $errno, $errstr, 5);
    $elapsed = round((microtime(true) - $start) * 1000);
    if ($fp) {
        echo "CONNECTED (" . $elapsed . "ms)\n";
        fclose($fp);
    } else {
        echo "FAILED ($errstr) [" . $elapsed . "ms]\n";
    }
}

// Check mail config
echo "\n--- Current Mail Config ---\n";
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "  default: " . config('mail.default') . "\n";
echo "  host: " . config('mail.mailers.smtp.host') . "\n";
echo "  port: " . config('mail.mailers.smtp.port') . "\n";
echo "  encryption: " . config('mail.mailers.smtp.encryption') . "\n";
echo "  username: " . (config('mail.mailers.smtp.username') ?: '(not set)') . "\n";
echo "  from_address: " . config('mail.from.address') . "\n";
echo "  from_name: " . config('mail.from.name') . "\n";

echo "\nDone.\n";
