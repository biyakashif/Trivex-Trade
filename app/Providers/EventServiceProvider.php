<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Add other event listeners here if needed
    ];

    public function boot()
    {
        // Update last_activity on login
        Event::listen(Login::class, function ($event) {
            $user = $event->user;
            Log::info('User logged in, updating last_activity', [
                'id' => $user->id,
                'email' => $user->email,
            ]);
            $user->last_activity = now();
            $user->save();
        });

        // Clear last_activity on logout
        Event::listen(Logout::class, function ($event) {
            $user = $event->user;
            Log::info('User logged out, clearing last_activity', [
                'id' => $user->id,
                'email' => $user->email,
            ]);
            $user->last_activity = null;
            $user->save();
        });
    }

    public function register()
    {
        //
    }
}