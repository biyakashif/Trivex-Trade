<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserIpLocation extends Model
{
    protected $fillable = [
        'user_id',
        'ip_address',
        'city',
        'region',
        'country',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}