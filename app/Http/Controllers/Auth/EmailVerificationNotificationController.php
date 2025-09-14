<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User; // Import the User model
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmailVerificationNotificationController extends Controller
{
    public function store(Request $request)
    {
        \Log::info('Starting EmailVerificationNotificationController::store', [
            'user_id' => $request->session()->get('user_id'),
            'memory_usage' => memory_get_usage() / 1024 / 1024 . ' MB',
        ]);

        $userId = $request->session()->get('user_id');
        if (!$userId) {
            \Log::warning('No user_id in session, redirecting to register');
            return redirect()->route('register')->withErrors(['message' => 'Session expired. Please register again.']);
        }

        $user = User::find($userId);
        if (!$user) {
            \Log::warning('User not found, redirecting to register');
            return redirect()->route('register')->withErrors(['message' => 'User not found. Please register again.']);
        }

        if ($user->hasVerifiedEmail()) {
            \Log::info('User email already verified, redirecting to dashboard', [
                'user_id' => $user->id,
                'memory_usage' => memory_get_usage() / 1024 / 1024 . ' MB',
            ]);
            return redirect()->intended('/dashboard');
        }

        \Log::info('Calling sendEmailVerificationNotification', [
            'user_id' => $user->id,
            'memory_usage' => memory_get_usage() / 1024 / 1024 . ' MB',
        ]);

        $user->sendEmailVerificationNotification();

        \Log::info('Finished sending email verification notification', [
            'user_id' => $user->id,
            'memory_usage' => memory_get_usage() / 1024 / 1024 . ' MB',
        ]);

        // Redirect back to the verification page with a success message
        return redirect()->route('verification.notice')->with('status', 'verification-link-sent');
    }
}