<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use GuzzleHttp\Client;

// Get a fresh token for the Doctor
$user = App\Models\User::where('email', 'zeno@oasis.com')->first();
$user->tokens()->delete(); // Clear old tokens
$token = $user->createToken('test-token')->plainTextToken;

echo 'Token: ' . substr($token, 0, 20) . '...' . PHP_EOL;
echo 'User ID: ' . $user->id . PHP_EOL;

// Make login request first to verify auth works
$client = new Client(['base_uri' => 'http://localhost:8050', 'http_errors' => false]);

echo PHP_EOL . '--- Test 1: GET /api/auth/me ---' . PHP_EOL;
$response = $client->get('/api/auth/me', [
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json',
        'X-User-Id' => (string)$user->id,
        'X-User-Role' => 'Doctor',
    ]
]);
echo 'Status: ' . $response->getStatusCode() . PHP_EOL;
echo 'Body: ' . $response->getBody() . PHP_EOL;

echo PHP_EOL . '--- Test 2: GET /api/animals ---' . PHP_EOL;
$response = $client->get('/api/animals?per_page=5', [
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json',
        'X-User-Id' => (string)$user->id,
        'X-User-Role' => 'Doctor',
    ]
]);
echo 'Status: ' . $response->getStatusCode() . PHP_EOL;
$body = json_decode($response->getBody(), true);
echo 'Animals count: ' . count($body['data'] ?? []) . PHP_EOL;

echo PHP_EOL . '--- Test 3: POST /api/animals (FormData simulation) ---' . PHP_EOL;
$response = $client->post('/api/animals', [
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json',
        'X-User-Id' => (string)$user->id,
        'X-User-Role' => 'Doctor',
    ],
    'form_params' => [
        'name' => 'Test Animal from Doctor',
        'species' => 'Camel',
        'gender' => 'Male',
        'owner_id' => '2',
    ]
]);
echo 'Status: ' . $response->getStatusCode() . PHP_EOL;
echo 'Body: ' . $response->getBody() . PHP_EOL;

echo PHP_EOL . '--- Test 4: POST /api/animals (JSON) ---' . PHP_EOL;
$response = $client->post('/api/animals', [
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
        'X-User-Id' => (string)$user->id,
        'X-User-Role' => 'Doctor',
    ],
    'json' => [
        'name' => 'Test Animal from Doctor JSON',
        'species' => 'Camel',
        'gender' => 'Male',
    ]
]);
echo 'Status: ' . $response->getStatusCode() . PHP_EOL;
echo 'Body: ' . $response->getBody() . PHP_EOL;

// Clean up: delete created test animals
App\Models\Animal::where('name', 'like', 'Test Animal%')->delete();
$user->tokens()->delete();
