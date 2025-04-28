<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class RolesAndAdminSeeder extends Seeder
{
    public function run()
    {
        $roles = ['client', 'dog_walker', 'admin', 'moderator'];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $usersData = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'role' => 'admin',
            ],
            [
                'name' => 'Client User',
                'email' => 'client@example.com',
                'role' => 'client',
            ],
            [
                'name' => 'Walker User',
                'email' => 'dog_walker@example.com',
                'role' => 'dog_walker',
            ],
            [
                'name' => 'Moderator User',
                'email' => 'moderator@example.com',
                'role' => 'moderator',
            ],
        ];

        foreach ($usersData as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'), 
                ]
            );

            $user->assignRole($userData['role']);
        }
    }
}

