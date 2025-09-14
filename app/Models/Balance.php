<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Balance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'usdt_balance',
        'btc_balance',
        'eth_balance',
    ];

    // Cast balance fields to float
    protected $casts = [
        'usdt_balance' => 'float',
        'btc_balance' => 'float',
        'eth_balance' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}