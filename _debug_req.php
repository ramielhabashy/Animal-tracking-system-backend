<?php
$user = App\Models\User::find(141);
echo 'User: ' . $user->name . ' (Role: ' . $user->getPrimaryRoleName() . ')' . PHP_EOL;

$request = Illuminate\Http\Request::create('/api/users', 'GET', ['per_page' => 100]);
$request->headers->set('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken);
$request->headers->set('Accept', 'application/json');

app()->instance('request', $request);

$kernel = app()->make(Illuminate\Contracts\Http\Kernel::class);

try {
    $response = $kernel->handle($request);
    echo 'Status: ' . $response->getStatusCode() . PHP_EOL;
    echo 'Body: ' . $response->getContent() . PHP_EOL;
} catch (Exception $e) {
    echo 'Exception: ' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
}
