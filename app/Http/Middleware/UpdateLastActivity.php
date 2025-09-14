<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Events\UserOnlineStatusUpdated;

class UpdateLastActivity
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('UpdateLastActivity middleware executed', [
            'request_path' => $request->path(),
            'method' => $request->method(),
            'user_id' => Auth::id(),
            'has_user' => Auth::check(),
            'session_id' => $request->session()->getId(),
        ]);

        if (Auth::check()) {
            $user = Auth::user();
            Log::info('Updating last_activity via middleware', [
                'id' => $user->id,
                'email' => $user->email,
                'request_path' => $request->path(),
                'method' => $request->method(),
            ]);
            $user->last_activity = now();
            $user->save();

            // Dispatch the new event
            event(new UserOnlineStatusUpdated($user->id, $user->isOnline()));
        } else {
            Log::warning('No authenticated user for last_activity update', [
                'request_path' => $request->path(),
                'method' => $request->method(),
                'session' => $request->session()->all(),
            ]);
        }

        return $next($request);
    }
}
// namespace App\Http\Middleware;

// use Closure;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Log;

// class UpdateLastActivity
// {
//     public function handle(Request $request, Closure $next)
//     {
//         Log::info('UpdateLastActivity middleware executed', [
//             'request_path' => $request->path(),
//             'method' => $request->method(),
//             'user_id' => Auth::id(),
//             'has_user' => Auth::check(),
//             'session_id' => $request->session()->getId(),
//         ]);

//         if (Auth::check()) {
//             $user = Auth::user();
//             Log::info('Updating last_activity via middleware', [
//                 'id' => $user->id,
//                 'email' => $user->email,
//                 'request_path' => $request->path(),
//                 'method' => $request->method(),
//             ]);
//             $user->last_activity = now();
//             $user->save();
//         } else {
//             Log::warning('No authenticated user for last_activity update', [
//                 'request_path' => $request->path(),
//                 'method' => $request->method(),
//                 'session' => $request->session()->all(),
//             ]);
//         }

//         return $next($request);
//     }
// }

