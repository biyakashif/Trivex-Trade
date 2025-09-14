<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'symbol',
        'direction',
        'delivery_time',
        'trade_amount',
        'trade_profit',
        'profit_earned',
        'status',
        'created_at', // Optional: Allow mass assignment of timestamps
        'updated_at', // Optional: Allow mass assignment of timestamps
    ];

    protected $casts = [
        'trade_amount' => 'float',
        'trade_profit' => 'float',
        'profit_earned' => 'float',
        'delivery_time' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}