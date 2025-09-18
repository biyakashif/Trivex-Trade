<?php

namespace App\Http\Controllers;

use App\Models\CryptoDepositDetail;
use App\Models\Wallet;
use App\Models\Balance; // Add this import
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class DepositController extends Controller
{
    public function index()
    {
        // Fetch the logged-in user's balances
        $balance = Balance::where('user_id', Auth::id())->first();

        // Prepare balances to pass to the frontend (default to 0 if no record exists)
        $balances = [
            'usdt_balance' => $balance ? $balance->usdt_balance : 0,
            'btc_balance' => $balance ? $balance->btc_balance : 0,
            'eth_balance' => $balance ? $balance->eth_balance : 0,
        ];

        return Inertia::render('Vendor/Deposit', [
            'balances' => $balances,
        ]);
    }

    public function show($symbol)
    {
        $depositDetail = CryptoDepositDetail::where('symbol', $symbol)->first();

        if (!$depositDetail) {
            abort(404, 'Cryptocurrency not found');
        }

        // Fetch the logged-in user's balances
        $balance = Balance::where('user_id', Auth::id())->first();

        // Prepare balances to pass to the frontend (default to 0 if no record exists)
        $balances = [
            'usdt_balance' => $balance ? $balance->usdt_balance : 0,
            'btc_balance' => $balance ? $balance->btc_balance : 0,
            'eth_balance' => $balance ? $balance->eth_balance : 0,
        ];

        // Static data for each cryptocurrency
        $details = [
            'usdt' => [
                'network' => 'Tron(TRC20)',
                'warning' => 'Ends with qW42ifz',
            ],
            'eth' => [
                'network' => 'Ethereum',
                'warning' => 'Ends with 4204179a14de91',
            ],
            'btc' => [
                'network' => 'Bitcoin',
                'warning' => 'ONLY use this address to deposit BTC. Please don\'t deposit inscriptions, NFTs, or any other non-BTC assets, as they can\'t be credited or returned.',
            ],
        ];

        // Get coin types for withdrawal
        $coinTypes = \App\Models\CoinType::all();

        return Inertia::render('Vendor/DepositDetails', [
            'symbol' => $symbol,
            'balances' => $balances,
            'coinTypes' => $coinTypes,
            'depositDetails' => [
                'qr_code' => $depositDetail->qr_code,
                'address' => $depositDetail->address,
                'network' => $details[$symbol]['network'],
                'warning' => $details[$symbol]['warning'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'symbol' => 'required|string|in:usdt,btc,eth',
            'amount' => 'required|numeric|min:0.00000001',
            'slip' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $file = $request->file('slip');
        if (!$file->isValid()) {
            return redirect()->back()->with('error', 'Invalid file upload.');
        }

        $destinationPath = storage_path('app/public/deposit_slips');
        $fileName = Auth::id() . '_' . $request->symbol . '_' . time() . '.' . $file->getClientOriginalExtension();

        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $file->move($destinationPath, $fileName);

        Wallet::create([
            'symbol' => $request->symbol,
            'amount' => $request->amount,
            'slip_path' => 'deposit_slips/' . $fileName,
            'status' => 'pending',
            'user_id' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Deposit submitted successfully! Awaiting approval.');
    }

    public function history(Request $request)
    {
        $symbol = $request->query('symbol');
        $deposits = Wallet::where('user_id', Auth::id())
            ->where('symbol', $symbol)
            ->orderBy('created_at', 'desc')
            ->get(['symbol', 'amount', 'status', 'created_at']);

        return response()->json([
            'deposits' => $deposits,
        ]);
    }

    public function swap()
    {
        $user = Auth::user();

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

        return Inertia::render('Vendor/Swap', [
            'balances' => $balanceData,
        ]);
    }

    public function performSwap(Request $request)
    {
        $request->validate([
            'from_crypto' => 'required|in:usdt,btc,eth',
            'to_crypto' => 'required|in:usdt,btc,eth',
            'from_amount' => 'required|numeric|min:0', // Original amount to deduct
            'to_amount' => 'required|numeric|min:0',   // Converted amount to add
        ]);

        $user = Auth::user();
        $balance = Balance::where('user_id', $user->id)->first();

        if (!$balance) {
            return redirect()->back()->with('error', 'Balance record not found.');
        }

        $fromCrypto = $request->from_crypto;
        $toCrypto = $request->to_crypto;
        $fromAmount = floatval($request->from_amount); // Amount to deduct (e.g., 50 USDT)
        $toAmount = floatval($request->to_amount);     // Converted amount to add (e.g., 0.00053 BTC)

        // Prevent swapping the same crypto
        if ($fromCrypto === $toCrypto) {
            return redirect()->back()->with('error', 'Cannot swap the same cryptocurrency.');
        }

        // Check if the user has sufficient balance
        $fromBalanceKey = $fromCrypto . '_balance';
        $toBalanceKey = $toCrypto . '_balance';

        if ($balance->$fromBalanceKey < $fromAmount) {
            return redirect()->back()->with('error', 'Insufficient balance for this swap.');
        }

        // Deduct from the source balance
        $balance->$fromBalanceKey -= $fromAmount;

        // Add the converted amount to the destination balance
        $balance->$toBalanceKey += $toAmount;

        $balance->save();

        return redirect()->route('deposit')->with('success', 'Swap completed successfully!');
    }
}