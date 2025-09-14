<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\Balance;
use App\Models\AdminMessage;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Display the vendor dashboard with balance and admin messages.
     */
    public function index()
    {
        try {
            $userId = Auth::id();
            if (!$userId) {
                Log::warning('No authenticated user found for dashboard access');
                return redirect()->route('login');
            }
            Log::info('Dashboard accessed by user: ' . $userId);

            // Fetch balance
            $balance = Balance::where('user_id', $userId)->first();
            $balanceData = $balance ? [
                'usdt_balance' => $balance->usdt_balance ?? 0,
                'btc_balance' => $balance->btc_balance ?? 0,
                'eth_balance' => $balance->eth_balance ?? 0,
            ] : [
                'usdt_balance' => 0,
                'btc_balance' => 0,
                'eth_balance' => 0,
            ];

            // Fetch active admin messages
            $adminMessages = AdminMessage::where('active', true)->get();

            // If no messages are found, use default messages
            if ($adminMessages->isEmpty()) {
                $adminMessages = collect([
                    ['message' => 'Welcome to your dashboard!', 'active' => true],
                    ['message' => 'Check your latest trades!', 'active' => true],
                    ['message' => 'Stay updated!', 'active' => true],
                ]);
            }

            return Inertia::render('Vendor/Dashboard', [
                'balance' => $balanceData,
                'adminMessages' => $adminMessages,
            ]);
        } catch (\Exception $e) {
            Log::error('Error rendering dashboard: ' . $e->getMessage());
            return Inertia::render('Error', [
                'message' => 'An error occurred while loading the dashboard. Please try again.',
            ]);
        }
    }

    /**
     * Fetch live balance data for the authenticated user.
     */
    public function getLiveBalances()
    {
        try {
            $userId = Auth::id();
            if (!$userId) {
                Log::warning('No authenticated user for live balances');
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $balance = Balance::where('user_id', $userId)->first();
            $balanceData = $balance ? [
                'usdt_balance' => $balance->usdt_balance ?? 0,
                'eth_balance' => $balance->eth_balance ?? 0,
                'btc_balance' => $balance->btc_balance ?? 0,
            ] : [
                'usdt_balance' => 0,
                'eth_balance' => 0,
                'btc_balance' => 0,
            ];

            return response()->json($balanceData);
        } catch (\Exception $e) {
            Log::error('Error fetching live balances: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch balances'], 500);
        }
    }

    /**
     * Fetch admin messages for all users.
     */
    public function getAdminMessages()
    {
        try {
            $adminMessages = AdminMessage::where('active', true)->get();
            // If no messages are found, use default messages
            if ($adminMessages->isEmpty()) {
                $adminMessages = collect([
                    ['message' => 'Welcome to your dashboard!', 'active' => true],
                    ['message' => 'Check your latest trades!', 'active' => true],
                    ['message' => 'Stay updated!', 'active' => true],
                ]);
            }
            return response()->json($adminMessages);
        } catch (\Exception $e) {
            Log::error('Error fetching admin messages: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch messages'], 500);
        }
    }
}