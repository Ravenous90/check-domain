<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'demo@demo.local'],
            [
                'name' => 'Demo',
                'password' => Hash::make('Test1234!'),
                'is_superuser' => true,
            ]
        );
    }
}
