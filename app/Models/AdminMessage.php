<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminMessage extends Model
{
    protected $table = 'admin_messages'; // Ensure this matches your database table name
    protected $fillable = ['message', 'active']; // Allow mass assignment
    protected $casts = [
        'active' => 'boolean',
    ];
}