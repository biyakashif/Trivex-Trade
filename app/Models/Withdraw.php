<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdraw extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'coin_id',
        'amount_withdraw',
        'status',
        'bank_account_number',
        'account_holder_name',
        'bank_name',
        'crypto_wallet',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coinType()
    {
        return $this->belongsTo(CoinType::class, 'coin_id');
    }
}