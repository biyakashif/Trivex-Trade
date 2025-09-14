<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = ['symbol', 'amount', 'slip_path', 'status', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}