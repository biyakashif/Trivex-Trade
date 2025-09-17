<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CryptoDepositDetail;
use App\Models\Wallet;
use App\Models\Balance;
use App\Models\User;
use App\Models\Trade;
use App\Models\Investment;
use App\Models\AdminSecurity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use App\Models\DeletedUser;
use App\Services\LocationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use App\Models\UserIpLocation;



class AdminController extends Controller
{
    public function dashboard()
    {
        return Inertia::render('Admin/Dashboard');
    }

       public function showQrUploadForm(Request $request)
    {
        return Inertia::render('Admin/QRAddressUpload', [
            'symbols' => ['usdt', 'btc', 'eth'],
        ]);
    }

    public function uploadQrAndAddress(Request $request)
    {
        $validated = $request->validate([
            'symbol' => 'required|in:usdt,btc,eth',
            'qr_code' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'address' => 'required|string|max:255',
        ]);

        $file = $request->file('qr_code');
        if (!$file->isValid()) {
            return redirect()->back()->with('error', 'Invalid file upload.');
        }

        $destinationPath = storage_path('app/public/qr_codes');
        $fileName = $validated['symbol'] . '_' . time() . '.' . $file->getClientOriginalExtension();

        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $file->move($destinationPath, $fileName);

        CryptoDepositDetail::updateOrCreate(
            ['symbol' => $validated['symbol']],
            [
                'qr_code' => 'qr_codes/' . $fileName,
                'address' => $validated['address'],
            ]
        );

        return redirect()->back()->with('success', 'QR code and wallet address uploaded successfully.');
    }

    public function checkSecuritySetup(Request $request)
    {
        $adminId = Auth::id();
        $security = AdminSecurity::where('admin_id', $adminId)->first();
        return response()->json([
            'hasSecurity' => !is_null($security),
        ]);
    }

    public function setupSecurity(Request $request)
    {
        $adminId = Auth::id();
        $validated = $request->validate([
            'answer1' => 'required|string|max:255',
            'answer2' => 'required|string|max:255',
            'answer3' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            AdminSecurity::create([
                'admin_id' => $adminId,
                'password' => Hash::make($validated['password']),
                'question1' => 'What is your best friend\'s name?',
                'answer1' => Hash::make($validated['answer1']),
                'question2' => 'What is your favorite pet\'s name?',
                'answer2' => Hash::make($validated['answer2']),
                'question3' => 'What is your birth city\'s name?',
                'answer3' => Hash::make($validated['answer3']),
            ]);
            Log::info('Security setup completed for admin', ['admin_id' => $adminId]);
            return redirect()->back()->with('success', 'Security setup successful');
        } catch (\Exception $e) {
            Log::error('Error setting up security: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Failed to setup security']);
        }
    }

    public function verifyPassword(Request $request)
    {
        $adminId = Auth::id();
        $validated = $request->validate([
            'password' => 'required|string',
        ]);

        $security = AdminSecurity::where('admin_id', $adminId)->first();
        if (!$security) {
            return redirect()->back()->withErrors(['error' => 'Security not set up']);
        }

        if (Hash::check($validated['password'], $security->password)) {
            Log::info('Password verified for admin', ['admin_id' => $adminId]);
            return redirect()->back()->with('success', 'Password verified');
        }

        Log::warning('Incorrect password attempt', ['admin_id' => $adminId]);
        return redirect()->back()->withErrors(['error' => 'Incorrect password']);
    }

    public function recoverPassword(Request $request)
    {
        $adminId = Auth::id();
        $validated = $request->validate([
            'questionIndex' => 'required|integer|in:1,2,3',
            'answer' => 'required|string|max:255',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $security = AdminSecurity::where('admin_id', $adminId)->first();
        if (!$security) {
            return redirect()->back()->withErrors(['error' => 'Security not set up']);
        }

        $answerField = 'answer' . $validated['questionIndex'];
        if (!Hash::check($validated['answer'], $security->$answerField)) {
            Log::warning('Incorrect recovery answer', ['admin_id' => $adminId, 'question_index' => $validated['questionIndex']]);
            return redirect()->back()->withErrors(['error' => 'Incorrect answer']);
        }

        try {
            $security->update([
                'password' => Hash::make($validated['new_password']),
            ]);
            Log::info('Password recovered successfully', ['admin_id' => $adminId]);
            return redirect()->back()->with('success', 'Password updated successfully');
        } catch (\Exception $e) {
            Log::error('Error recovering password: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Failed to update password']);
        }
    }



    public function showUpdateWallet(Request $request)
    {
        $userId = $request->query('user_id');
        $walletQuery = Wallet::with('user')
            ->orderBy('created_at', 'desc');

        if ($userId) {
            $walletQuery->where('user_id', $userId);
        }

        $wallets = $walletQuery->get(['id', 'user_id', 'symbol', 'amount', 'slip_path', 'status', 'created_at']);
        $groupedWallets = [
            'usdt' => $wallets->where('symbol', 'usdt')->values(),
            'btc' => $wallets->where('symbol', 'btc')->values(),
            'eth' => $wallets->where('symbol', 'eth')->values(),
        ];

        $balances = null;
        if ($userId) {
            $balance = Balance::where('user_id', $userId)->first();
            $balances = $balance ? [
                'usdt_balance' => $balance->usdt_balance,
                'btc_balance' => $balance->btc_balance,
                'eth_balance' => $balance->eth_balance,
            ] : [
                'usdt_balance' => 0,
                'btc_balance' => 0,
                'eth_balance' => 0,
            ];
        }

        if ($request->wantsJson()) {
            return response()->json([
                'props' => [
                    'groupedWallets' => $groupedWallets,
                    'selectedUserId' => $userId,
                    'balances' => $balances,
                ],
            ]);
        }

        return Inertia::render('Admin/UpdateWallet', [
            'groupedWallets' => $groupedWallets,
            'selectedUserId' => $userId,
            'balances' => $balances,
        ]);
    }

    public function updateWallet(Request $request, $walletId)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
        ]);

        $wallet = Wallet::findOrFail($walletId);
        $action = $request->input('action');

        if ($action === 'approve') {
            $wallet->update(['status' => 'approved']);
            $balance = Balance::where('user_id', $wallet->user_id)->first();
            if (!$balance) {
                $balance = Balance::create([
                    'user_id' => $wallet->user_id,
                    'current_balance' => 0,
                    'usdt_balance' => 0,
                    'btc_balance' => 0,
                    'eth_balance' => 0,
                ]);
            }

            if ($wallet->symbol === 'usdt') {
                $balance->increment('usdt_balance', $wallet->amount);
            } elseif ($wallet->symbol === 'btc') {
                $balance->increment('btc_balance', $wallet->amount);
            } elseif ($wallet->symbol === 'eth') {
                $balance->increment('eth_balance', $wallet->amount);
            }
        } elseif ($action === 'reject') {
            $wallet->update(['status' => 'rejected']);
        }

        return redirect()->back()->with('success', "Deposit has been {$action}d successfully.");
    }

    public function updateBalance(Request $request, $userId)
    {
        $request->validate([
            'crypto' => 'required|in:usdt,btc,eth',
            'amount' => 'required|numeric',
            'action' => 'required|in:add,subtract',
        ]);

        $crypto = $request->input('crypto');
        $amount = (float) $request->input('amount');
        $action = $request->input('action');

        $balance = Balance::where('user_id', $userId)->first();
        if (!$balance) {
            $balance = Balance::create([
                'user_id' => $userId,
                'current_balance' => 0,
                'usdt_balance' => 0,
                'btc_balance' => 0,
                'eth_balance' => 0,
            ]);
        }

        $balanceColumn = $crypto . '_balance';
        if ($action === 'add') {
            $balance->increment($balanceColumn, $amount);
            $actionText = 'added to';
        } else {
            $newBalance = $balance->$balanceColumn - $amount;
            if ($newBalance < 0) {
                return redirect()->back()->with('error', "Cannot subtract more than the current {$crypto} balance ({$balance->$balanceColumn}).");
            }
            $balance->decrement($balanceColumn, $amount);
            $actionText = 'subtracted from';
        }

        return redirect()->back()->with('success', "Successfully {$actionText} {$amount} from {$crypto} balance.");
    }

    public function showDepositClients(Request $request)
    {
        try {
            if ($request->wantsJson()) {
                $hasWalletsTable = Schema::hasTable('wallets');
                $query = User::select('users.id', 'users.name', 'users.email')
                             ->distinct('users.id');

                if ($hasWalletsTable) {
                    $query->leftJoin('wallets', 'users.id', '=', 'wallets.user_id')
                          ->orderByRaw('COALESCE(MAX(wallets.created_at), users.created_at) DESC')
                          ->groupBy('users.id', 'users.name', 'users.email');
                } else {
                    $query->orderBy('users.created_at', 'desc');
                }

                $users = $query->paginate(10);
                $users->getCollection()->transform(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                });

                Log::info('Deposit Clients Fetched:', [
                    'total' => $users->total(),
                    'data' => $users->items(),
                ]);

                return response()->json([
                    'data' => $users->items(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'total' => $users->total(),
                ]);
            }

            return Inertia::render('Admin/DepositClients', [
                'initialUsers' => [],
                'initialPage' => 1,
                'initialLastPage' => 1,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching deposit clients: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Failed to fetch users', 'message' => $e->getMessage()], 500);
            }
            throw $e;
        }
    }

    public function tradeLoss(Request $request)
    {
        try {
            $hasBalanceTable = Schema::hasTable('balances');
            $query = User::select('users.id', 'users.name', 'users.email', 'users.loss_applied');

            if ($hasBalanceTable) {
                $query->leftJoin('balances', 'users.id', '=', 'balances.user_id')
                      ->orderByRaw('COALESCE(balances.updated_at, users.created_at) DESC');
            } else {
                $query->orderBy('users.created_at', 'desc');
            }

            $users = $query->paginate(10);
            $users->getCollection()->transform(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'loss_applied' => $user->loss_applied ?? false,
                ];
            });

            Log::info('Trade Loss Users Fetched:', [
                'total' => $users->total(),
                'data' => $users->items(),
            ]);

            return response()->json([
                'data' => $users->items(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching trade loss users: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Failed to fetch users', 'message' => $e->getMessage()], 500);
        }
    }

    public function updateLoss(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);
            $user->loss_applied = $request->input('loss_applied', false);
            $user->save();

            return response()->json(['message' => 'Loss status updated successfully']);
        } catch (\Exception $e) {
            Log::error('Error updating loss status: ' . $e->getMessage(), ['user_id' => $userId, 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Failed to update loss status', 'message' => $e->getMessage()], 500);
        }
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

    public function getMessages()
    {
        try {
            $messages = AdminMessage::where('active', true)->limit(3)->get()->map(function ($message) {
                return $message->message;
            })->values()->all();

            // Pad with empty strings if less than 3 messages
            while (count($messages) < 3) {
                $messages[] = '';
            }

            return response()->json(['messages' => $messages]);
        } catch (\Exception $e) {
            Log::error('Error fetching admin messages: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch messages'], 500);
        }
    }

    public function storeMessage(Request $request)
    {
        try {
            $validated = $request->validate([
                'messages' => 'required|array',
                'messages.*' => 'string|max:255',
            ]);

            $messages = array_slice(array_filter($validated['messages']), 0, 3); // Take first 3 non-empty messages
            AdminMessage::truncate(); // Clear existing messages
            foreach ($messages as $index => $message) {
                AdminMessage::create([
                    'message' => $message,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return response()->json(['message' => 'Messages saved successfully']);
        } catch (\Exception $e) {
            Log::error('Error saving admin messages: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to save messages'], 500);
        }
    }
    public function getUsers(Request $request)
    {
        $users = User::select(['id', 'name', 'email', 'created_at', 'is_blocked', 'email_verified_at'])
            ->orderBy('created_at', 'desc')
            ->with(['emailVerificationCode'])
            ->paginate(25);
        
        // Add online status to each user
        $users->getCollection()->transform(function ($user) {
            $user->is_online = $user->isOnline();
            return $user;
        });

        \Log::info('Users fetched:', $users->toArray()); // Debug: Log the users
        if ($request->wantsJson()) {
            return response()->json($users);
        }
        
        return Inertia::render('Admin/Users', ['users' => $users]);
    }

    public function blockUser(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $wasBlocked = $user->is_blocked;
            $user->is_blocked = !$user->is_blocked;
            $user->save();

            if ($user->is_blocked && !$wasBlocked) {
                if (Auth::check() && Auth::id() === $user->id) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return redirect('/login')->with('error', 'Your account has been blocked. Please contact support.');
                }
            }

            return redirect()->back()->with('success', 'User status updated successfully');
        } catch (\Exception $e) {
            Log::error('Error blocking user: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update user status');
        }
    }

    public function deleteUser(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            // Collect all related data before deletion
            $originalUserData = [
                'balance' => $user->balance ? $user->balance->toArray() : null,
                'wallets' => $user->wallets->map(function ($wallet) {
                    return $wallet->toArray();
                })->toArray(),
                'withdraws' => $user->withdraws->map(function ($withdraw) {
                    return $withdraw->toArray();
                })->toArray(),
                'email_verification_code' => $user->emailVerificationCode ? $user->emailVerificationCode->toArray() : null,
            ];

            // Create deleted user record
            DeletedUser::create([
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'password' => $user->password,
                'role' => $user->role,
                'status' => $user->status,
                'remember_token' => $user->remember_token,
                'last_activity' => $user->last_activity,
                'is_blocked' => $user->is_blocked,
                'loss_applied' => $user->loss_applied,
                'deleted_at' => now(),
                'deleted_by_admin_id' => Auth::id(),
                'original_user_data' => $originalUserData,
            ]);

            // Now actually delete the user and related records
            $user->delete();

            return redirect()->back()->with('success', 'User deleted successfully');
        } catch (\Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete user');
        }
    }

public function approveUser(Request $request, $id)
{
    try {
        $user = User::findOrFail($id);
        if ($user->email_verified_at) {
            return redirect()->back()->with('error', 'User is already verified');
        }
        
        $now = now(); // Get the current timestamp
        $user->email_verified_at = $now;
        $user->created_at = $now; // Set created_at to the same timestamp
        $user->remember_token = Str::random(60);
        $user->save();

        return redirect()->back()->with('success', 'User approved successfully');
    } catch (\Exception $e) {
        Log::error('Error approving user: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Failed to approve user');
    }
}
    
        public function tradeHistory(Request $request)
    {
        $users = User::select(['users.id', 'users.email'])
            ->join('trades', 'users.id', '=', 'trades.user_id')
            ->distinct()
            ->orderBy('users.email')
            ->paginate(25);

        return Inertia::render('Admin/TradeHistory', [
            'users' => $users,
        ]);
    }

    public function getUserTradeHistory(Request $request, $userId)
    {
        Log::info('getUserTradeHistory method called', [
            'user_id' => $userId,
            'admin' => Auth::check() ? Auth::user()->toArray() : 'No authenticated admin',
        ]);

        // Check if user is authenticated and has admin role
        if (!Auth::check()) {
            Log::error('Unauthorized access to user trade history: No authenticated user', ['user_id' => $userId]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (Auth::user()->role !== 'admin') {
            Log::error('Unauthorized access to user trade history: User is not admin', ['user_id' => $userId]);
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

            Log::info('Trades fetched for user ' . $userId . ': ' . $trades->toJson());
            return response()->json(['history' => $trades], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching trade history: ' . $e->getMessage());
            return response()->json(['error' => 'Server error while fetching trade history'], 500);
        }
    }
    

    public function investmentHistory(Request $request)
    {
        Log::info('investmentHistory route accessed');
        $users = User::select(['users.id', 'users.email'])
            ->join('investments', 'users.id', '=', 'investments.user_id')
            ->distinct()
            ->orderBy('users.email')
            ->paginate(25);

        return Inertia::render('Admin/InvestmentHistory', [
            'users' => $users,
        ]);
    }

    public function getUserInvestmentHistory(Request $request, $userId)
    {
        Log::info('getUserInvestmentHistory route accessed', [
            'user_id' => $userId,
            'admin' => Auth::check() ? Auth::user()->toArray() : 'No authenticated admin',
            'request_url' => $request->fullUrl(),
        ]);

        // Check if user is authenticated and has admin role
        if (!Auth::check()) {
            Log::error('Unauthorized access to user investment history: No authenticated user', ['user_id' => $userId]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (Auth::user()->role !== 'admin') {
            Log::error('Unauthorized access to user investment history: User is not admin', ['user_id' => $userId]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $investments = Investment::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($investment) {
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

            Log::info('Investments fetched for user ' . $userId . ': ' . $investments->toJson());
            return response()->json(['investments' => $investments], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching investment history: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Server error while fetching investment history'], 500);
        }
    }


    protected $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

public function showUserIpLocation(Request $request)
{
    $locations = UserIpLocation::with('user')
        ->orderBy('created_at', 'desc')
        ->paginate(15);

    // Debug the data structure
    \Log::info('Location data:', $locations->toArray());

    return Inertia::render('Admin/UserIpLocation', [
        'savedLocations' => [
            'data' => $locations->items(),
            'current_page' => $locations->currentPage(),
            'per_page' => $locations->perPage(),
            'last_page' => $locations->lastPage(),
            'from' => $locations->firstItem(),
            'to' => $locations->lastItem(),
            'total' => $locations->total(),
            'links' => $locations->linkCollection()->toArray(),
            'prev_page_url' => $locations->previousPageUrl(),
            'next_page_url' => $locations->nextPageUrl(),
        ],
    ]);
}

    public function storeUserIpLocation(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip',
            'city' => 'nullable|string',
            'region' => 'nullable|string',
            'country' => 'nullable|string',
        ]);

        $userId = auth()->id();
        UserIpLocation::create([
            'user_id' => $userId,
            'ip_address' => $request->ip_address,
            'city' => $request->city,
            'region' => $request->region,
            'country' => $request->country,
        ]);

        return redirect()->back()->with('success', 'Location data saved successfully.');
    }

    public function getDeletedUsers(Request $request)
    {
        $deletedUsers = DeletedUser::with('deletedByAdmin')
            ->orderBy('deleted_at', 'desc')
            ->paginate(25);

        if ($request->wantsJson()) {
            return response()->json($deletedUsers);
        }

        return Inertia::render('Admin/DeletedUsers', ['deletedUsers' => $deletedUsers]);
    }

    public function restoreUser(Request $request, $id)
    {
        try {
            $deletedUser = DeletedUser::findOrFail($id);
            $restoredUser = $deletedUser->restore();

            return redirect()->back()->with('success', 'User restored successfully');
        } catch (\Exception $e) {
            Log::error('Error restoring user: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to restore user');
        }
    }
}