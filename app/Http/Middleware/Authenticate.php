<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // For Inertia.js requests, return null to let Inertia handle the redirect
        if ($request->header('X-Inertia')) {
            return null;
        }

        return $request->expectsJson() ? null : route('login');
    }

    /**
     * Handle an incoming request.
     */
    public function handle($request, \Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);

        // Check if the authenticated user is blocked
        if (Auth::check() && Auth::user()->is_blocked) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/login')->with('error', 'Your account has been blocked. Please contact support.');
        }

        return $next($request);
    }
}