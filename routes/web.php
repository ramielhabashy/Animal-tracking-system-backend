<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

// Web login for Horizon dashboard (session-based auth)
Route::get('/login', function () {
    if (Auth::check()) {
        return redirect('/horizon');
    }
    $token = csrf_token();
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Oasis - Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, sans-serif; background: #f5f5f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #fff; border-radius: 12px; padding: 40px; width: 380px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        h1 { font-size: 24px; margin-bottom: 8px; color: #002819; }
        p { color: #666; margin-bottom: 24px; font-size: 14px; }
        label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 6px; color: #333; }
        input { width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px; font-size: 15px; margin-bottom: 16px; }
        input:focus { outline: none; border-color: #D4AF37; box-shadow: 0 0 0 3px rgba(212,175,55,0.15); }
        button { width: 100%; padding: 12px; background: #002819; color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; }
        button:hover { background: #003d28; }
        .error { background: #fef2f2; color: #dc2626; padding: 10px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; display: none; }
        .success { background: #f0fdf4; color: #16a34a; padding: 10px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; display: none; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Oasis Trace</h1>
        <p>Login to access the Horizon dashboard</p>
        <div class="error" id="error"></div>
        <div class="success" id="success"></div>
        <form id="loginForm" method="POST" action="/login">
            <input type="hidden" name="_token" value="{$token}">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" required placeholder="admin@oasis.com">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required placeholder="Enter your password">
            <button type="submit">Sign in</button>
        </form>
        <p style="margin-top: 16px; text-align: center; font-size: 12px; color: #999;">
            Admin: admin@oasis.com / password
        </p>
    </div>
</body>
</html>
HTML;
});

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        return redirect('/horizon');
    }

    return back()->withErrors([
        'email' => 'Invalid credentials.',
    ]);
})->middleware('web');

// Test route: dispatch a job to verify Horizon works
Route::get('/test-job', function () {
    $user = \App\Models\User::where('email', 'admin@oasis.com')->first();
    if (!$user) {
        return 'No admin user found. Run php artisan db:seed first.';
    }

    // Send mail via the queue (async) instead of sync
    \Illuminate\Support\Facades\Mail::to($user->email)->queue(
        new \App\Mail\NotificationMail(
            subject: 'Horizon Test - ' . now()->format('H:i:s'),
            greeting: 'Hello Admin!',
            lines: ['This email was queued and should appear in Horizon.']
        )
    );

    return 'Job dispatched! Check Horizon at <a href="/horizon">/horizon</a>';
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->middleware('web');

Route::get('/{any}', function () {
    $indexFile = public_path('index.html');
    if (file_exists($indexFile)) {
        return file_get_contents($indexFile);
    }
    return response('index.html not found. Run npm run build in frontend/.', 404);
})->where('any', '^(?!api|sanctum|telescope|horizon|login).*$');
