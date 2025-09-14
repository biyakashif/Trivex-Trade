<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CryptoDepositDetail extends Model
{
    protected $fillable = ['symbol', 'qr_code', 'address'];
}