<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Balance;
use App\Models\User;
use App\Models\Withdraw;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
class WithdrawController extends Controller
{

public function index()
{
    $withdrawals = User::with(['withdraws' => function ($query) {
        $query->with('coinType')->orderBy('created_at', 'desc');
    }])
    ->leftJoinSub(
        Withdraw::select('user_id', DB::raw('MAX(CASE WHEN status IN ("pending", "Under Review") THEN 1 ELSE 0 END) as has_pending'))
            ->groupBy('user_id'),
        'withdraws_agg',
        'users.id',
        '=',
        'withdraws_agg.user_id'
    )
    ->select('users.id', 'users.name', 'users.email', 'users.created_at')
    ->orderByRaw('COALESCE(withdraws_agg.has_pending, 0) DESC')
    ->orderBy('users.created_at', 'desc')
    ->get();

    return Inertia::render('Admin/Withdrawals', [
        'withdrawals' => $withdrawals,
    ]);
}
    public function approve($id)
    {
        $withdraw = Withdraw::findOrFail($id);

        if ($withdraw->status !== 'Under Review') {
            return redirect()->back()->with('error', 'This withdrawal has already been processed.');
        }

        $withdraw->status = 'approved';
        $withdraw->approved_at = now();
        $withdraw->save();

        return redirect()->back()->with('success', 'Withdrawal approved successfully.');
    }

    public function reject($id)
    {
        $withdraw = Withdraw::with('coinType')->findOrFail($id);

        if ($withdraw->status !== 'Under Review') {
            return redirect()->back()->with('error', 'This withdrawal has already been processed.');
        }

        $user = $withdraw->user;
        $balance = Balance::where('user_id', $user->id)->first();

        if (!$balance) {
            return redirect()->back()->with('error', 'User balance not found.');
        }

        $amount = $withdraw->amount_withdraw;

        if ($withdraw->coin_id) {
            // Crypto withdrawal - add balance back
            $symbol = strtolower($withdraw->coinType->symbol);
            $balanceKey = $symbol . '_balance';
            $balance->$balanceKey += $amount;
        } else {
            // Bank withdrawal (assumed in USDT) - add balance back
            $balance->usdt_balance += $amount;
        }

        $balance->save();

        $withdraw->status = 'rejected';
        $withdraw->rejected_at = now();
        $withdraw->save();

        return redirect()->back()->with('success', 'Withdrawal rejected successfully.');
    }

    public function edit($id)
    {
        $withdraw = Withdraw::with('coinType')->findOrFail($id);

        return Inertia::render('Admin/WithdrawEdit', [
            'withdraw' => $withdraw,
        ]);
    }

    public function update(Request $request, $id)
    {
        $withdraw = Withdraw::findOrFail($id);

        $validated = $request->validate([
            'amount_withdraw' => 'required|numeric|min:0.00000001',
            'wallet_address' => 'nullable|string|alpha_num', // For crypto
            'account_holder_name' => 'nullable|string|max:255', // For bank
            'bank_name' => 'nullable|string|max:255', // For bank
            'bank_account_number' => 'nullable|string|min:8', // For bank
        ]);

        $withdraw->amount_withdraw = $validated['amount_withdraw'];

        if ($withdraw->coin_id) {
            // Crypto withdrawal
            $withdraw->bank_account_number = $validated['wallet_address'];
            $withdraw->crypto_wallet = $validated['wallet_address'];
        } else {
            // Bank withdrawal
            $withdraw->account_holder_name = $validated['account_holder_name'];
            $withdraw->bank_name = $validated['bank_name'];
            $withdraw->bank_account_number = $validated['bank_account_number'];
        }

        $withdraw->save();

        return redirect()->route('admin.withdrawals')->with('success', 'Withdrawal updated successfully!');
    }
}