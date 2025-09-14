<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('admin', function ($user) {
            Log::info('Checking admin gate for user:', [
                'user_id' => $user->id,
                'role' => $user->role,
                'is_admin' => $user->role === 'admin',
            ]);
            return $user->role === 'admin';
        });
    }
}