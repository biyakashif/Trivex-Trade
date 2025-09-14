<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\EmailVerificationCode;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Carbon\Carbon;

class RegisteredUserController extends Controller
{
    public function create()
    {
        if (Cache::get('registrationDisabled')) {
            abort(403, 'Registrations are currently disabled.');
        }

        return Inertia::render('Auth/Register');
    }

    public function store(Request $request)
    {
        if (Cache::get('registrationDisabled')) {
            abort(403, 'Registrations are currently disabled.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => strtolower($request->email),
            'password' => Hash::make($request->password),
        ]);

        $user->sendEmailVerificationNotification();
        event(new Registered($user));
        $request->session()->put('user_id', $user->id);

        return redirect()->route('verification.notice');
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $userId = $request->session()->get('user_id');
        if (!$userId) {
            \Log::warning('No user_id in session during verification');
            return redirect()->route('register')->withErrors(['message' => 'Session expired. Please register again.']);
        }

        $user = User::find($userId);
        if (!$user) {
            \Log::warning('User not found during verification', ['user_id' => $userId]);
            return redirect()->route('register')->withErrors(['message' => 'User not found. Please register again.']);
        }

        $verificationCode = $user->emailVerificationCode()
            ->where('code', $request->code)
            ->where('expires_at', '>=', Carbon::now())
            ->first();

        if (!$verificationCode) {
            \Log::warning('Invalid or expired verification code', ['code' => $request->code]);
            return redirect()->back()->withErrors(['code' => 'Invalid or expired verification code.']);
        }

        $user->email_verified_at = Carbon::now();
        $user->save();

        $verificationCode->delete();
        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->forget('user_id');

        if (!Auth::check()) {
            \Log::error('User not authenticated after login', ['user_id' => $user->id]);
            return redirect()->route('login')->withErrors(['message' => 'Failed to log in after verification.']);
        }

        \Log::info('Redirecting to dashboard after verification', ['user_id' => $user->id]);
        return redirect()->intended(route('dashboard'));
    }
}
