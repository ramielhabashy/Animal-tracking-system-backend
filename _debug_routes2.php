<?php
$routes = Route::getRoutes();
foreach ($routes->getRoutesByMethod()['GET'] ?? [] as $route) {
    if ($route->uri === 'api/users' || $route->uri === 'api/auth/me') {
        echo 'URI: ' . $route->uri . PHP_EOL;
        echo 'Action: ' . $route->getActionName() . PHP_EOL;
        echo 'Middleware: ' . json_encode($route->gatherMiddleware()) . PHP_EOL;
        echo PHP_EOL;
    }
}
