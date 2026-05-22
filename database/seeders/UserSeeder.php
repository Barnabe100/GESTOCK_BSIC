<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use \App\Models\User;


class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'testadmin@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('12345678'),
            ]
        );

        $user = User::firstOrCreate(
            ['email' => 'testdemande@gmail.com'],
            [
                'name' => 'User',
                'password' => Hash::make('12345678'),
            ]
        );

        // Assigner rôles
        $admin->assignRole('Admin');
        $user->assignRole('Demandeur');
    }
}
