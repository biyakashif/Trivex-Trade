<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinType extends Model
{
    use HasFactory;

    protected $fillable = ['coin_name', 'symbol'];
}