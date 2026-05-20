<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user) {
            return $user->hasRole('Admin');
        });
    }

    protected function authorization(): void
    {
        $this->gate();

        Horizon::auth(function ($request) {
            return $request->user() && $request->user()->hasRole('Admin');
        });
    }
}
