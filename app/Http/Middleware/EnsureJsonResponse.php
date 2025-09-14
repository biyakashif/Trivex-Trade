<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureJsonResponse
{
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');

        $response = $next($request);

        if ($request->expectsJson() || $request->is('api/*')) {
            if (!Auth::check()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            if (!$response->isOk() && !$response->getContent()) {
                return response()->json(['error' => $response->statusText()], $response->status());
            }
        }

        return $response;
    }
}