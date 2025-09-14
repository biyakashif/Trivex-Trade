<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || ($request->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$request->user()->hasVerifiedEmail())) {
            $isAjax = $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest';
            if ($isAjax) {
                return response()->json(['error' => 'Email not verified'], 403);
            }

            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}