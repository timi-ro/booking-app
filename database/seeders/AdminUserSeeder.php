<?php

namespace Database\Seeders;

use App\Auth\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        User::updateOrCreate(
            [
                'email' => 'admin@example.com',
                'name' => 'Admin',
                'password' => Hash::make('AdM!nU$3rStR0nGP@ssW0rd'),
                'role' => 'admin',
            ]
        );
    }
}
