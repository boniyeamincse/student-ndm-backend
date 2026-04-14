<?php

namespace Database\Seeders;

use App\Enum\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@studentmovment-ndm.com'],
            [
                'name'       => 'Super Admin',
                'password'   => Hash::make('password123@ChangeMe'),
                'status'     => UserStatus::Active,
                'role_type'  => 'superadmin',
            ]
        );

        // Assign superadmin role (which carries all permissions)
        $user->assignRole('superadmin');

        $this->command->info('Superadmin account ready: admin@studentmovment-ndm.com');
        $this->command->warn('IMPORTANT: Change the default password immediately after first login!');
    }
}
