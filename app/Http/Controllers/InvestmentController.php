<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Investment;
use App\Models\Balance;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class InvestmentController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $balance = Balance::where('user_id', $userId)->first();
        $usdtBalance = $balance ? $balance->usdt_balance : 0;

        // Fetch active investments
        $activeInvestments = Investment::where('user_id', $userId)
            ->where('status', 'active')
            ->get()
            ->map(function ($investment) {
                // Check if the investment period has ended
                if (Carbon::now()->greaterThanOrEqualTo($investment->ends_at)) {
                    $this->completeInvestment($investment);
                    $investment->refresh();
                }

                return [
                    'id' => $investment->id,
                    'plan' => $investment->plan,
                    'amount' => $investment->amount,
                    'profit' => $investment->profit,
                    'starts_at' => $investment->starts_at->toDateTimeString(),
                    'ends_at' => $investment->ends_at->toDateTimeString(),
                    'status' => $investment->status,
                ];
            });

        return Inertia::render('Vendor/Investment', [
            'usdt_balance' => $usdtBalance,
            'active_investments' => $activeInvestments,
        ]);
    }

    public function store(Request $request)
    {
        $userId = Auth::id();
        $balance = Balance::where('user_id', $userId)->first();

        $plans = [
            '7_days' => ['days' => 7, 'profit' => 0.10, 'min' => 1000],
            '15_days' => ['days' => 15, 'profit' => 0.25, 'min' => 10000],
            '30_days' => ['days' => 30, 'profit' => 0.50, 'min' => 30000],
            '60_days' => ['days' => 60, 'profit' => 0.90, 'min' => 50000],
        ];

        $validated = $request->validate([
            'plan' => 'required|in:7_days,15_days,30_days,60_days',
            'amount' => 'required|numeric|min:0',
        ]);

        $plan = $plans[$validated['plan']];
        $amount = $validated['amount'];

        if ($amount < $plan['min']) {
            return response()->json(['error' => "Minimum investment for {$validated['plan']} plan is {$plan['min']} USDT."], 400);
        }

        if (!$balance || $balance->usdt_balance < $amount) {
            return response()->json(['error' => 'Insufficient USDT balance to start this investment.'], 400);
        }

        $profit = $amount * $plan['profit'];
        $startsAt = Carbon::now();
        $endsAt = $startsAt->copy()->addDays($plan['days']);

        $investment = Investment::create([
            'user_id' => $userId,
            'plan' => $validated['plan'],
            'amount' => $amount,
            'profit' => $profit,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => 'active',
        ]);

        // Deduct the investment amount from the balance
        $balance->usdt_balance -= $amount;
        $balance->save();

        return response()->json([
            'investment' => [
                'id' => $investment->id,
                'plan' => $investment->plan,
                'amount' => $investment->amount,
                'profit' => $investment->profit,
                'starts_at' => $investment->starts_at->toDateTimeString(),
                'ends_at' => $investment->ends_at->toDateTimeString(),
                'status' => $investment->status,
            ],
        ], 200);
    }

    protected function completeInvestment($investment)
    {
        if ($investment->status === 'completed') {
            return;
        }

        $userId = $investment->user_id;
        $balance = Balance::where('user_id', $userId)->first();

        if ($balance) {
            $balance->usdt_balance += $investment->amount + $investment->profit;
            $balance->save();
        }

        $investment->status = 'completed';
        $investment->save();
    }
}