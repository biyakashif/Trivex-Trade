<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Vendor\DashboardController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\WithdrawController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\Admin\WithdrawController as AdminWithdrawController;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Carbon\Carbon;

// Debug log to confirm routes are loaded
Log::info('Loading web routes from routes/web.php');

/**
 * Public Routes
 * Accessible to all users, including guests.
 */
Route::get('/about', function () {
    return Inertia::render('About');
})->name('about');
Route::middleware(['web', 'guest'])->group(function () {
    Route::get('/', function () {
        $registrationDisabled = Cache::get('registrationDisabled', false);
        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register') && !$registrationDisabled,
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
        ]);
    })->name('welcome');

    Route::get('/settings/registration-status', fn () => response()->json([
        'registrationDisabled' => Cache::get('registrationDisabled', false),
    ]))->name('public.settings.registration_status');
});

/**
 * Authenticated Routes (Vendor/User)
 * Routes accessible to all authenticated users (vendors and admins).
 */
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/live-balances', [DashboardController::class, 'getLiveBalances'])->name('live-balances');
    Route::get('/admin-messages', [DashboardController::class, 'getAdminMessages'])->name('admin.messages');

    Route::post('/update-last-activity', function () {
        if (!Auth::check()) {
            Log::warning('No authenticated user for last_activity update');
            return redirect()->back()->with('error', 'Not authenticated');
        }

        $user = Auth::user();
        Log::info('Updating last_activity via route', [
            'id' => $user->id,
            'email' => $user->email,
        ]);
        $user->last_activity = now();
        $user->save();

        return redirect()->back();
    })->middleware(['web', 'auth']);

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::controller(TradeController::class)->group(function () {
        Route::get('/trade', 'index')->name('trade');
        Route::post('/trade/store', 'storeTrade')->name('trade.store');
        Route::get('/trade/history', 'getTradeHistory')->name('trade.history');
        Route::get('/check-loss-status', 'checkLossStatus')->name('trade.check_loss_status');
        Route::get('/current-balance', 'checkCurrentBalance')->name('trade.current_balance');
    });

    Route::get('/trade/{symbol}', fn ($symbol) =>
        Inertia::render('Vendor/TradeView', ['symbol' => $symbol])
    )->name('vendor.trade.view');

    Route::get('/trade/{symbol}/order', fn ($symbol) =>
        Inertia::render('Vendor/Trade', ['symbol' => $symbol])
    )->name('vendor.trade.order');

    Route::controller(DepositController::class)->group(function () {
        Route::get('/deposit', 'index')->name('deposit');
        Route::get('/deposit/history', 'history')->name('deposit.history');
        Route::get('/deposit/{symbol}', 'show')->name('deposit.details');
        Route::post('/deposit', 'store')->name('deposit.store');
        Route::get('/swap', 'swap')->name('swap.index');
        Route::post('/swap', 'performSwap')->name('swap.perform');
    });

    Route::get('/account', fn () => Inertia::render('Vendor/Account'))->name('account');

    Route::get('/withdraw', [WithdrawController::class, 'index'])->name('withdraw');
    Route::post('/withdraw/store', [WithdrawController::class, 'store'])->name('withdraw.store');

    Route::get('/investment', [InvestmentController::class, 'index'])->name('investment');
    Route::post('/investment/store', [InvestmentController::class, 'store'])->name('investment.store');
});

/**
 * Admin Routes
 * Routes restricted to authenticated admin users.
 */
Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/user-ip-location', [AdminController::class, 'showUserIpLocation'])->name('admin.user-ip-location');
        Route::post('/user-ip-location', [AdminController::class, 'storeUserIpLocation'])->name('admin.store-user-ip-location');
        Route::post('/ip-locations/auto-detect', [AdminController::class, 'autoDetectAndStore']);

        Route::get('/trade-history', [AdminController::class, 'tradeHistory'])->name('admin.trade-history');
        Route::get('/trade-history/{userId}', [AdminController::class, 'getUserTradeHistory'])->name('admin.trade-history.user');

        Route::get('/investment-history', [AdminController::class, 'investmentHistory'])->name('admin.investment-history');
        Route::get('/investment-history/{userId}', [AdminController::class, 'getUserInvestmentHistory'])->name('admin.investment-history.user');

        Route::get('/online-users', [\App\Http\Controllers\Api\UserController::class, 'getOnlineUsers'])->name('admin.online-users');
        Route::get('/user/{id}', [\App\Http\Controllers\Api\UserController::class, 'getUser'])->name('admin.user');
        
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

        Route::get('/messages', [AdminController::class, 'getMessages'])->name('admin.messages.index');
        Route::post('/messages', [AdminController::class, 'storeMessage'])->name('admin.messages.store');

        Route::get('/settings/registration-status', fn () => response()->json([
            'registrationDisabled' => Cache::get('registrationDisabled', false),
        ]))->name('admin.settings.registration_status');

        Route::post('/settings/registration-status', function (Request $request) {
            $request->validate(['registrationDisabled' => 'required|boolean']);
            Cache::put('registrationDisabled', $request->registrationDisabled, now()->addDays(30));
            return redirect()->back()->with('success', 'Registration status updated successfully.');
        })->name('admin.settings.update_registration_status');

        Route::get('/qr-address-upload', [AdminController::class, 'showQrUploadForm'])->name('admin.qr-address-upload');
        Route::post('/qr-address-upload', [AdminController::class, 'uploadQrAndAddress'])->name('admin.qr-address-upload.store');
        Route::get('/check-security-setup', [AdminController::class, 'checkSecuritySetup'])->name('admin.check-security-setup');
        Route::post('/setup-security', [AdminController::class, 'setupSecurity'])->name('admin.setup-security');
        Route::post('/verify-password', [AdminController::class, 'verifyPassword'])->name('admin.verify-password');
        Route::post('/recover-password', [AdminController::class, 'recoverPassword'])->name('admin.recover-password');




        Route::get('/deposit', fn () => Inertia::render('Admin/Deposit'))->name('admin.deposit');
        Route::get('/new-users', fn () => Inertia::render('Admin/NewUsers'))->name('admin.new-users');
        Route::get('/withdraws', fn () => Inertia::render('Admin/Withdraws'))->name('admin.withdraws');

        Route::get('/update-wallet', [AdminController::class, 'showUpdateWallet'])->name('admin.update-wallet');
        Route::post('/update-wallet/{walletId}', [AdminController::class, 'updateWallet'])->name('admin.update-wallet.update');
        Route::post('/update-wallet/balance/{userId}', [AdminController::class, 'updateBalance'])->name('admin.update-wallet.balance');

        Route::get('/deposit-clients', [AdminController::class, 'showDepositClients'])->name('admin.deposit-clients');

        Route::get('/trade-loss', fn () => Inertia::render('Admin/LossApply'))->name('admin.trade-loss');
        Route::get('/trade-loss-data', [AdminController::class, 'tradeLoss'])->name('admin.trade-loss-data');
        Route::post('/trade-loss/update-loss/{userId}', [AdminController::class, 'updateLoss'])->name('admin.update-loss');

        Route::get('/withdrawals', [AdminWithdrawController::class, 'index'])->name('admin.withdrawals');
        Route::post('/withdrawals/approve/{id}', [AdminWithdrawController::class, 'approve'])->name('admin.withdrawals.approve');
        Route::post('/withdrawals/reject/{id}', [AdminWithdrawController::class, 'reject'])->name('admin.withdrawals.reject');
        Route::get('/withdrawals/edit/{id}', [AdminWithdrawController::class, 'edit'])->name('admin.withdrawals.edit');
        Route::post('/withdrawals/update/{id}', [AdminWithdrawController::class, 'update'])->name('admin.withdrawals.update');

        Route::get('/manage-messages', fn () => Inertia::render('Admin/ManageMessages'))->name('admin.manage.messages');

        Route::get('/users', [AdminController::class, 'getUsers'])->name('admin.users.index');
        Route::post('/users/block/{id}', [AdminController::class, 'blockUser'])->name('admin.users.block');
        Route::post('/users/delete/{id}', [AdminController::class, 'deleteUser'])->name('admin.users.delete');
        Route::post('/users/approve/{id}', [AdminController::class, 'approveUser'])->name('admin.users.approve');
    });

/**
 * General Authenticated Routes
 * Routes accessible to all authenticated users, outside admin scope.
 */
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Authentication Routes
require __DIR__ . '/auth.php';