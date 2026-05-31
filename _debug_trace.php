<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Simulate a request to /api/users with a Bearer token
use Illuminate\Http\Request;
use App\Models\User;

// Get the Doctor user
$user = User::find(141);
echo "User: {$user->name} (ID: {$user->id}, Role: {$user->getPrimaryRoleName()})" . PHP_EOL;
echo "Managed by: {$user->managed_by}" . PHP_EOL;

// Create a token
$token = $user->createToken('debug-token');
echo "Token: " . substr($token->plainTextToken, 0, 30) . "..." . PHP_EOL;

// Create a request to /api/users
$request = Request::create('/api/users', 'GET', ['per_page' => 100]);
$request->headers->set('Authorization', 'Bearer ' . $token->plainTextToken);
$request->headers->set('Accept', 'application/json');

echo PHP_EOL . "Dispatching request..." . PHP_EOL;

try {
    $response = $kernel->handle($request);
    echo "Status: " . $response->getStatusCode() . PHP_EOL;
    echo "Body: " . $response->getContent() . PHP_EOL;
} catch (Exception $e) {
    echo "Exception: " . get_class($e) . ": " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
}
