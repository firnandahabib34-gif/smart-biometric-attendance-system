<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin Smart Biometric',
            'email' => 'admin@smartbiometric.com',
            'password' => Hash::make('hahahaha123'),
        ]);
    }
}
