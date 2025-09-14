<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Providers\RouteServiceProvider;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            if (Auth::user()->role === 'admin') {
                \Log::info('Authenticated admin attempted to access welcome page, redirecting to admin dashboard', [
                    'user_id' => Auth::id(),
                    'email' => Auth::user()->email,
                ]);
                return redirect('/admin/dashboard');
            }
            \Log::info('Authenticated user attempted to access welcome page, redirecting to user dashboard', [
                'user_id' => Auth::id(),
                'email' => Auth::user()->email,
            ]);
            return redirect(RouteServiceProvider::HOME);
        }

        \Log::info('Unauthenticated user accessing welcome page');
        return $next($request);
    }
}