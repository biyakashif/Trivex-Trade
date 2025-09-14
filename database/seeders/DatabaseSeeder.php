<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // Add this import
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@gmail.com'], // Condition
            [ // Values to insert or update
                'name' => 'Admin user',
                'password' => bcrypt('Zaho@Admin007'),
                'role' => 'admin',
                'status' => 'active',
            ]
        );
    
        DB::table('users')->updateOrInsert(
            ['email' => 'user@gmail.com'], // Condition
            [
                'name' => 'user',
                'password' => bcrypt('Zaho@User007'),
                'role' => 'user',
                'status' => 'active',
            ]
        );
    }
}
