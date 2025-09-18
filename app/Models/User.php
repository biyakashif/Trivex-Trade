<?php

namespace App\Models;

use App\Events\UserOnlineStatusUpdated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\VerifyEmailCodeNotification;
use App\Notifications\ResetPassword as ResetPasswordNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'email_verified_at', 'password', 'role', 'status', 
        'remember_token', 'created_at', 'updated_at', 'last_activity', 'is_blocked', 'loss_applied', 'avatar'
    ];    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $appends = ['is_online'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'loss_applied' => 'boolean',
            'is_blocked' => 'boolean',
            'last_activity' => 'datetime',
        ];
    }

    public static function booted()
    {
        static::updated(function ($user) {
            if ($user->isDirty('last_activity')) {
                Log::info('Dispatching UserOnlineStatusUpdated', [
                    'user_id' => $user->id,
                    'is_online' => $user->isOnline(),
                ]);
                event(new UserOnlineStatusUpdated($user->id, $user->isOnline()));
            }
        });
    }

    public function getIsOnlineAttribute()
    {
        $isOnline = $this->last_activity && $this->last_activity->diffInMinutes(now()) <= 5;
        Log::info('Checking is_online for user', [
            'id' => $this->id,
            'last_activity' => $this->last_activity,
            'is_online' => $isOnline,
        ]);
        return $isOnline;
    }

    public function isOnline()
    {
        return $this->getIsOnlineAttribute();
    }

    public function balance()
    {
        return $this->hasOne(Balance::class);
    }

    public function wallets()
    {
        return $this->hasMany(Wallet::class, 'user_id', 'id');
    }

    public function withdraws()
    {
        return $this->hasMany(Withdraw::class, 'user_id', 'id');
    }

    public function emailVerificationCode()
    {
        return $this->hasOne(EmailVerificationCode::class);
    }

    public function sendEmailVerificationNotification()
    {
        Log::info('Starting sendEmailVerificationNotification for user ID: ' . $this->id);
        $this->emailVerificationCode()
            ->where('expires_at', '<', Carbon::now())
            ->delete();
        Log::info('Deleted expired codes for user ID: ' . $this->id);
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        Log::info('Generated verification code: ' . $code);
        $this->emailVerificationCode()->updateOrCreate(
            ['user_id' => $this->id],
            [
                'code' => $code,
                'expires_at' => Carbon::now()->addMinutes(15),
            ]
        );
        Log::info('Email verification code saved for user ID: ' . $this->id);
        try {
            $this->notify(new VerifyEmailCodeNotification($code));
            Log::info('Email notification sent for user ID: ' . $this->id);
        } catch (\Exception $e) {
            Log::error('Failed to send email notification for user ID: ' . $this->id, [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function sendPasswordResetNotification($token)
    {
        Log::info('Starting sendPasswordResetNotification for user ID: ' . $this->id);
        try {
            $this->notify(new ResetPasswordNotification($token));
            Log::info('Password reset notification sent for user ID: ' . $this->id);
        } catch (\Exception $e) {
            Log::error('Failed to send password reset notification for user ID: ' . $this->id, [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}