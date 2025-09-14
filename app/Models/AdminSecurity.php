<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminSecurity extends Model
{
    use HasFactory;

    protected $table = 'admin_security';

    protected $fillable = [
        'admin_id',
        'password',
        'question1',
        'answer1',
        'question2',
        'answer2',
        'question3',
        'answer3',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}