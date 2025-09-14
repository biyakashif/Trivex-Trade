<?php

namespace Database\Seeders;

use App\Models\AdminMessage;
use Illuminate\Database\Seeder;

class AdminMessageSeeder extends Seeder
{
    public function run()
    {
        AdminMessage::create(['message' => 'Welcome to your dashboard!']);
        AdminMessage::create(['message' => 'Check your latest trades!']);
        AdminMessage::create(['message' => 'Stay updated!']);
    }
}