<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password; // Import the Password facade
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     *
     * @param Request $request
     * @return Response
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @param Request $request
     * @return \Inertia\Response|\Illuminate\Http\RedirectResponse
     */
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'token' => 'required',
    //         'email' => 'required|email',
    //         'password' => 'required|min:8|confirmed',
    //     ]);

    //     $status = Password::reset(
    //         $request->only('email', 'password', 'password_confirmation', 'token'),
    //         function ($user) use ($request) {
    //             $user->forceFill([
    //                 'password' => Hash::make($request->password),
    //                 'remember_token' => Str::random(60),
    //             ])->save();

    //             event(new PasswordReset($user));
    //         }
    //     );

    //     if ($status === Password::PASSWORD_RESET) {
    //         return redirect()->route('login')->with('status', 'Your password has been reset successfully. Please log in with your new password.');
    //     }

    //     // Pass the error back to the Inertia form
    //     return back()->withErrors(['email' => __($status)])->with('status', __($status));
    // }
//     public function store(Request $request)
// {
//     \Log::info('Password reset attempt for email: ' . $request->email);
//     $request->validate([
//         'token' => 'required',
//         'email' => 'required|email',
//         'password' => 'required|min:8|confirmed',
//     ]);

//     $status = Password::reset(
//         $request->only('email', 'password', 'password_confirmation', 'token'),
//         function ($user) use ($request) {
//             $user->forceFill([
//                 'password' => Hash::make($request->password),
//                 'remember_token' => Str::random(60),
//             ])->save();

//             event(new PasswordReset($user));
//         }
//     );

//     if ($status === Password::PASSWORD_RESET) {
//         \Log::info('Password reset successful for email: ' . $request->email);
//         return redirect()->route('login')->with('status', 'Your password has been reset successfully. Please log in with your new password.');
//     }

//     \Log::error('Password reset failed for email: ' . $request->email . ', status: ' . $status);
//     return back()->withErrors(['email' => __($status)])->with('status', __($status));
// }
public function store(Request $request)
{
    \Log::info('Password reset attempt for email: ' . $request->email);
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|min:8|confirmed',
    ]);

    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function ($user) use ($request) {
            $user->forceFill([
                'password' => Hash::make($request->password),
                'remember_token' => Str::random(60),
            ])->save();
            \Log::info('Password reset for user: ' . $user->email);
            event(new PasswordReset($user));
        }
    );

    if ($status === Password::PASSWORD_RESET) {
        \Log::info('Password reset successful for email: ' . $request->email);
        $request->session()->forget('url.intended');
        return redirect()->route('login')->with('status', 'Your password has been reset successfully. Please log in with your new password.');
    }

    \Log::error('Password reset failed for email: ' . $request->email . ', status: ' . $status);
    return back()->withErrors(['email' => __($status)])->with('status', __($status));
}
}