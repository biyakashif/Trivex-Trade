<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeletedUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'email_verified_at', 'password', 'role', 'status',
        'remember_token', 'last_activity', 'is_blocked', 'loss_applied',
        'deleted_at', 'deleted_by_admin_id', 'original_user_data'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_activity' => 'datetime',
        'deleted_at' => 'datetime',
        'is_blocked' => 'boolean',
        'loss_applied' => 'boolean',
        'original_user_data' => 'array',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Get the admin who deleted this user
     */
    public function deletedByAdmin()
    {
        return $this->belongsTo(User::class, 'deleted_by_admin_id');
    }

    /**
     * Restore the user back to the users table
     */
    public function restore()
    {
        // Create new user from deleted user data
        $userData = [
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'password' => $this->password,
            'role' => $this->role,
            'status' => $this->status,
            'remember_token' => $this->remember_token,
            'last_activity' => $this->last_activity,
            'is_blocked' => $this->is_blocked,
            'loss_applied' => $this->loss_applied,
        ];

        // Create the user
        $restoredUser = User::create($userData);

        // If we have original user data with relationships, restore them
        if ($this->original_user_data) {
            // Restore balance if it existed
            if (isset($this->original_user_data['balance'])) {
                $restoredUser->balance()->create($this->original_user_data['balance']);
            }

            // Restore wallets if they existed
            if (isset($this->original_user_data['wallets'])) {
                foreach ($this->original_user_data['wallets'] as $wallet) {
                    $restoredUser->wallets()->create($wallet);
                }
            }

            // Restore withdraws if they existed
            if (isset($this->original_user_data['withdraws'])) {
                foreach ($this->original_user_data['withdraws'] as $withdraw) {
                    $restoredUser->withdraws()->create($withdraw);
                }
            }
        }

        // Delete from deleted_users table
        $this->delete();

        return $restoredUser;
    }

    /**
     * Get formatted deleted date
     */
    public function getFormattedDeletedDateAttribute()
    {
        return $this->deleted_at->format('Y-m-d H:i:s');
    }
}
