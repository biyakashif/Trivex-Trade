<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;

use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        //
    }

    /**
     * Handle unauthenticated requests.
     */


    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson() || $request->isXmlHttpRequest()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }
    
        return redirect()->guest(route('login'));
    }
}