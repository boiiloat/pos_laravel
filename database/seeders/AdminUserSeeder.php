<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'fullname' => 'System Admin',
            'username' => 'admin',
            'password' => Hash::make('password123'),
            'role_id' => 1, 
            'create_date' => now(),
            'create_by' => 'System',
            'is_delete' => false,
        ]);
    }
}
