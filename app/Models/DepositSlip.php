<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepositSlip extends Model
{
    protected $fillable = ['user_id', 'symbol', 'amount', 'slip_path'];
}