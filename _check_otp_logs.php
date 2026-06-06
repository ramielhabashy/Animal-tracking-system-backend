<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Check email_logs for OTP-related sends
echo "=== Recent Email Logs (last 10) ===\n";
$logs = DB::table('email_logs')->orderBy('id', 'desc')->limit(10)->get();
foreach ($logs as $log) {
    $subj = mb_substr((string)$log->subject, 0, 60);
    $err = mb_substr((string)$log->error_message, 0, 100);
    echo "#{$log->id} | {$log->status} | To:{$log->recipient} | Subj:{$subj}\n";
    if ($log->error_message) {
        echo "  Error: {$err}\n";
    }
}

echo "\n=== Check Horizon status ===\n";
try {
    $client = new Predis\Client(['scheme' => 'tcp', 'host' => '127.0.0.1', 'port' => 6379]);
    $len = $client->llen('queues:default');
    echo "Queue length: $len\n";
    
    // Check Horizon's master process key
    $master = $client->get('horizon:master-supervisor');
    echo "Horizon master: " . ($master ?: 'not found') . "\n";
} catch (\Exception $e) {
    echo "Redis error: " . $e->getMessage() . "\n";
}

// Current mail config
echo "\n=== Mail Config ===\n";
echo "default: " . config('mail.default') . "\n";
echo "host: " . config('mail.mailers.smtp.host') . "\n";
echo "port: " . config('mail.mailers.smtp.port') . "\n";
echo "encryption: " . config('mail.mailers.smtp.encryption') . "\n";
