<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check class autoloading
$classes = [
    App\Http\Controllers\DashboardController::class,
    App\Http\Controllers\Api\Users\UserController::class,
    App\Models\User::class,
];
foreach ($classes as $c) {
    echo $c . ': ' . (class_exists($c) ? 'FOUND' : 'NOT FOUND') . PHP_EOL;
}

echo PHP_EOL . "--- Routes matching api/users ---" . PHP_EOL;
$routes = Route::getRoutes()->getRoutesByMethod();
foreach ($routes['GET'] ?? [] as $route) {
    if (strpos($route->uri, 'users') !== false) {
        echo 'URI: ' . $route->uri . PHP_EOL;
        echo '  Methods: ' . json_encode($route->methods()) . PHP_EOL;
        echo '  Middleware: ' . json_encode($route->middleware()) . PHP_EOL;
        echo '  Name: ' . $route->getName() . PHP_EOL;
        echo PHP_EOL;
    }
}
