<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function getOnlineUsers(Request $request)
    {
        Log::info('getOnlineUsers accessed', [
            'user' => Auth::user() ? Auth::user()->toArray() : 'No authenticated user',
        ]);

        try {
            $users = User::select('id', 'name', 'email')
                ->where('role', '!=', 'admin')
                ->where('last_activity', '>=', now()->subMinutes(5))
                ->get();

            Log::info('Fetched online users for API', [
                'total' => $users->count(),
                'users' => $users->toArray(),
            ]);

            return response()->json($users, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching online users for API: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch online users'], 500);
        }
    }

    public function getUser($id)
    {
        Log::info('getUser accessed', [
            'user_id' => $id,
            'authenticated_user' => Auth::user() ? Auth::user()->toArray() : 'No authenticated user',
        ]);

        try {
            $user = User::select('id', 'name', 'email')
                ->where('id', $id)
                ->where('role', '!=', 'admin')
                ->first();

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            Log::info('Fetched user for API', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json($user, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching user for API: ' . $e->getMessage());
            return response()->json(['error' => 'User not found'], 404);
        }
    }
}