<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CheckRegistrationEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Cache::get('registrationDisabled', false)) {
            return redirect()->route('welcome')->with('error', 'Registration is currently disabled.');
        }

        return $next($request);
    }
}