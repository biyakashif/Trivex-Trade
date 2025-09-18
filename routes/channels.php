<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Auth;
/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Broadcast::channel('admin.users', function ($user) {
//     return $user->role === 'admin'; // Only admins can listen
// });



Broadcast::channel('online-users', function ($user) {
    return Auth::check();
});

// User-specific channel for balance updates
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});



// Broadcast::channel('admin.users', function ($user) {
//     return Auth::check() && $user->role === 'admin';
// });