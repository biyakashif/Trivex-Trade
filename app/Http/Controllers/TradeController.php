<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Balance;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TradeController extends Controller
{
    public function index()
    {
        return Inertia::render('Vendor/Trade');
    }

    public function view($symbol)
    {
        return Inertia::render('Vendor/TradeView', [
            'symbol' => $symbol,
        ]);
    }

    public function checkLossStatus(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $lossApplied = $user->loss_applied ?? false;

        return response()->json(['lossApplied' => $lossApplied]);
    }

    public function checkCurrentBalance(Request $request)
    {
        $userId = Auth::id();
        $currentBalance = Balance::where('user_id', $userId)->sum('usdt_balance');
        return response()->json(['current_balance' => $currentBalance]);
    }

    public function storeTrade(Request $request)
    {
        $userId = Auth::id();
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'symbol' => 'required|string',
            'direction' => 'required|in:up,fall',
            'delivery_time' => 'required|integer',
            'trade_amount' => 'required|numeric|min:0',
            'trade_profit' => 'required|numeric',
        ]);

        // Fetch current balance
        $currentBalance = Balance::where('user_id', $userId)->first();
        if (!$currentBalance || $currentBalance->usdt_balance < $validated['trade_amount']) {
            return response()->json(['error' => 'Insufficient balance'], 400);
        }

        // Create the trade
        $trade = Trade::create([
            'user_id' => $userId,
            'symbol' => $validated['symbol'],
            'direction' => $validated['direction'],
            'delivery_time' => $validated['delivery_time'],
            'trade_amount' => $validated['trade_amount'],
            'trade_profit' => $validated['trade_profit'],
            'status' => 'pending',
        ]);

        // Deduct the trade amount from the balance
        $currentBalance->usdt_balance -= $validated['trade_amount'];
        $currentBalance->save();

        // Check loss status
        $lossApplied = $user->loss_applied ?? false;

        if ($lossApplied) {
            $trade->status = 'loss';
            $trade->profit_earned = 0;
        } else {
            $trade->status = 'completed';
            $trade->profit_earned = $validated['trade_profit'];
            $currentBalance->usdt_balance += $validated['trade_amount'] + $validated['trade_profit'];
            $currentBalance->save();
        }

        $trade->save();

        // Return Inertia response with trade and lossApplied props
        return Inertia::render('Vendor/TradeView', [
            'symbol' => $validated['symbol'],
            'trade' => [
                'id' => $trade->id,
                'symbol' => $trade->symbol,
                'direction' => $trade->direction,
                'trade_amount' => $trade->trade_amount,
                'profit_earned' => $trade->profit_earned,
                'status' => $trade->status,
                'created_at' => $trade->created_at->toDateTimeString(),
            ],
            'lossApplied' => $lossApplied,
        ]);
    }

    public function getTradeHistory(Request $request)
    {
        \Log::info('getTradeHistory method called', [
            'headers' => $request->headers->all(),
            'user' => Auth::check() ? Auth::user()->toArray() : 'No authenticated user',
        ]);

        $userId = Auth::id();
        if (!$userId) {
            \Log::error('User not authenticated');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $trades = Trade::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($trade) {
                    return [
                        'id' => $trade->id,
                        'symbol' => $trade->symbol,
                        'direction' => $trade->direction,
                        'delivery_time' => $trade->delivery_time,
                        'trade_amount' => $trade->trade_amount,
                        'trade_profit' => $trade->trade_profit,
                        'profit_earned' => $trade->profit_earned,
                        'status' => $trade->status,
                        'created_at' => $trade->created_at,
                    ];
                });

            \Log::info('Trades fetched for user ' . $userId . ': ' . $trades->toJson());
            return response()->json(['history' => $trades], 200);
        } catch (\Exception $e) {
            \Log::error('Error fetching trade history: ' . $e->getMessage());
            return response()->json(['error' => 'Server error while fetching trade history'], 500);
        }
    }
}