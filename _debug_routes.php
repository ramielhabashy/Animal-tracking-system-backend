<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$routes = Route::getRoutes()->getRoutesByMethod()['GET'] ?? [];
foreach ($routes as $route) {
    if (strpos($route->uri, 'api/users') !== false) {
        echo 'URI: ' . $route->uri . PHP_EOL;
        echo 'Middleware: ' . json_encode($route->middleware()) . PHP_EOL;
        echo 'Action: ' . $route->getActionName() . PHP_EOL;
        echo PHP_EOL;
    }
}
