<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use App\Providers\RouteServiceProvider;
use App\Events\UserActivityUpdated;
use App\Models\UserIpLocation;
use App\Services\LocationService;

class AuthenticatedSessionController extends Controller
{
    protected $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        Log::info('Attempting to log in user', [
            'email' => $request->email,
        ]);

        $credentials = $request->only('email', 'password');
        $credentials['email'] = strtolower($credentials['email']);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $request->session()->forget('url.intended');

            $user = Auth::user();
            Log::info('Login successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
            ]);

            // Capture IP and location on login
            $ip = $request->ip();
            $locationData = $this->locationService->fetchLocationData($ip);

            try {
                // Save location to user_ip_locations table
                $userIpLocation = UserIpLocation::create([
                    'user_id' => $user->id,
                    'ip_address' => $ip,
                    'city' => $locationData['city'] ?? 'Unknown',
                    'region' => $locationData['region'] ?? 'Unknown',
                    'country' => $locationData['country'] ?? 'Unknown',
                ]);

                Log::info('User location saved', [
                    'user_id' => $user->id,
                    'ip' => $ip,
                    'city' => $locationData['city'],
                    'region' => $locationData['region'],
                    'country' => $locationData['country'],
                ]);

                // Broadcast location and online status via Pusher
                event(new UserActivityUpdated($user->id, true, [
                    'ip_address' => $ip,
                    'city' => $locationData['city'] ?? 'Unknown',
                    'region' => $locationData['region'] ?? 'Unknown',
                    'country' => $locationData['country'] ?? 'Unknown',
                ]));
            } catch (\Exception $e) {
                Log::error('Failed to save user location', [
                    'user_id' => $user->id,
                    'ip' => $ip,
                    'error' => $e->getMessage(),
                ]);
            }

            if ($user->role === 'admin') {
                Log::info('Redirecting admin to admin dashboard');
                return redirect('/admin/dashboard');
            }

            Log::info('Redirecting user to vendor dashboard');
            return redirect('/dashboard');
        }

        Log::warning('Login failed: Credentials do not match', [
            'email' => $request->email,
        ]);

        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();
            Log::info('User logging out, clearing last_activity', [
                'id' => $user->id,
                'email' => $user->email,
            ]);
            $user->last_activity = null;
            $user->save();

            // Broadcast offline status without location data
            event(new UserActivityUpdated($user->id, false));
        } else {
            Log::warning('No authenticated user during logout attempt', [
                'session_id' => $request->session()->getId(),
            ]);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('welcome')->with('status', 'You have been logged out successfully.');
    }
}