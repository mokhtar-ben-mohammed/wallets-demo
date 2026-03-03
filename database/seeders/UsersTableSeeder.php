<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Mokhtar Ghaleb',
            'email' => 'mokhtar@gmail.com',
            'password' => Hash::make('123456'),
            'mobile' => '775038005',
        ]);

        User::create([
            'name' => 'Ahmed Ali',
            'email' => 'ahmed@example.com',
            'password' => Hash::make('123456'),
            'mobile' => '770000001',
        ]);

        User::create([
            'name' => 'Sara Mohammed',
            'email' => 'sara@example.com',
            'password' => Hash::make('123456'),
            'mobile' => '770000002',
        ]);
    }
}