<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/{any}', function () {
    $indexFile = public_path('index.html');
    if (file_exists($indexFile)) {
        return file_get_contents($indexFile);
    }
    return response('index.html not found. Run npm run build in frontend/.', 404);
})->where('any', '^(?!api|sanctum|telescope|horizon).*$');
