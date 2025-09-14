<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User; // Import the User model
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmailVerificationPromptController extends Controller
{
    public function __invoke(Request $request)
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return redirect()->route('login')->withErrors(['message' => 'Please register or log in to verify your email.']);
        }

        $user = User::find($userId);
        if (!$user) {
            return redirect()->route('login')->withErrors(['message' => 'User not found.']);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME);
        }

        return Inertia::render('Auth/VerifyEmail');
    }
}