<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\CoinType;
use App\Models\Withdraw;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WithdrawController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Fetch the user's balances
        $balance = Balance::where('user_id', $user->id)->first();
        $balanceData = $balance ? [
            'usdt_balance' => $balance->usdt_balance ?? 0,
            'btc_balance' => $balance->btc_balance ?? 0,
            'eth_balance' => $balance->eth_balance ?? 0,
        ] : [
            'usdt_balance' => 0,
            'btc_balance' => 0,
            'eth_balance' => 0,
        ];

        // Fetch the user's withdrawal history
        $withdrawals = Withdraw::where('user_id', $user->id)
            ->with('coinType')
            ->orderBy('created_at', 'desc')
            ->get();

        // Fetch available cryptocurrencies
        $coinTypes = CoinType::all();

        return Inertia::render('Vendor/Withdraw', [
            'balances' => $balanceData,
            'withdrawals' => $withdrawals,
            'coinTypes' => $coinTypes,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $balance = Balance::where('user_id', $user->id)->first();

        if (!$balance) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Balance record not found.'], 400);
            }
            return redirect()->back()->with('error', 'Balance record not found.');
        }

        // Check if it's a crypto withdrawal
        if ($request->has('coin_id')) {
            $validated = $request->validate([
                'coin_id' => 'required|exists:coin_types,id',
                'amount_withdraw' => 'required|numeric|min:0.00000001',
                'wallet_address' => 'required|string|alpha_num',
            ]);

            $coin = CoinType::find($validated['coin_id']);
            $symbol = strtolower($coin->symbol);
            $balanceKey = $symbol . '_balance';

            if ($balance->$balanceKey < $validated['amount_withdraw']) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['error' => 'Insufficient balance for this withdrawal.'], 400);
                }
                return redirect()->back()->with('error', 'Insufficient balance for this withdrawal.');
            }

            // Deduct balance immediately
            $balance->$balanceKey -= $validated['amount_withdraw'];
            $balance->save();

            Withdraw::create([
                'user_id' => $user->id,
                'coin_id' => $validated['coin_id'],
                'amount_withdraw' => $validated['amount_withdraw'],
                'status' => 'Under Review',
                'bank_account_number' => $validated['wallet_address'],
                'crypto_wallet' => $validated['wallet_address'],
            ]);
        }
        // Bank withdrawal
        else {
            $validated = $request->validate([
                'account_holder_name' => 'required|string|max:255',
                'bank_name' => 'required|string|max:255',
                'bank_account_number' => 'required|string|min:8',
                'bank_withdraw_amount' => 'required|numeric|min:0.00000001',
            ]);

            // For bank withdrawals, we'll assume the amount is in USDT
            if ($balance->usdt_balance < $validated['bank_withdraw_amount']) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['error' => 'Insufficient USDT balance for this withdrawal.'], 400);
                }
                return redirect()->back()->with('error', 'Insufficient USDT balance for this withdrawal.');
            }

            // Deduct balance immediately
            $balance->usdt_balance -= $validated['bank_withdraw_amount'];
            $balance->save();

            Withdraw::create([
                'user_id' => $user->id,
                'amount_withdraw' => $validated['bank_withdraw_amount'],
                'status' => 'Under Review',
                'account_holder_name' => $validated['account_holder_name'],
                'bank_name' => $validated['bank_name'],
                'bank_account_number' => $validated['bank_account_number'],
            ]);
        }

        // Return appropriate response based on request type
        if ($request->header('X-Inertia')) {
            // For Inertia requests, return a redirect back with success message
            return redirect()->back()->with('success', 'Withdrawal request submitted successfully.');
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => 'Withdrawal request submitted successfully.',
                'success' => true
            ]);
        }

        return redirect()->route('withdraw')->with('success', 'Withdrawal request submitted successfully.');
    }

    public function history(Request $request)
    {
        $symbol = $request->query('symbol');

        // If no symbol is provided, return all withdrawals for the user
        if (!$symbol) {
            $withdrawals = Withdraw::where('user_id', Auth::id())
                ->with('coinType')
                ->orderBy('created_at', 'desc')
                ->get([
                    'id',
                    'amount_withdraw',
                    'status',
                    'crypto_wallet',
                    'account_holder_name',
                    'bank_name',
                    'bank_account_number',
                    'created_at',
                    'coin_id'
                ]);

            return response()->json([
                'withdrawals' => $withdrawals,
            ]);
        }

        // Find the coin type by symbol
        $coinType = CoinType::where('symbol', $symbol)->first();

        if (!$coinType) {
            return response()->json(['withdrawals' => []]);
        }

        $withdrawals = Withdraw::where('user_id', Auth::id())
            ->where('coin_id', $coinType->id)
            ->with('coinType')
            ->orderBy('created_at', 'desc')
            ->get([
                'amount_withdraw',
                'status',
                'crypto_wallet',
                'created_at',
                'coin_id'
            ]);

        // Format the data to match the frontend expectations
        $formattedWithdrawals = $withdrawals->map(function ($withdrawal) {
            return [
                'id' => $withdrawal->id,
                'symbol' => $withdrawal->coinType->symbol,
                'amount' => $withdrawal->amount_withdraw,
                'status' => $withdrawal->status,
                'wallet_address' => $withdrawal->crypto_wallet,
                'created_at' => $withdrawal->created_at,
            ];
        });

        return response()->json([
            'withdrawals' => $formattedWithdrawals,
        ]);
    }
}