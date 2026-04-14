<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeder execution order matters:
     * 1. Permissions must exist before roles can be assigned permissions.
     * 2. Roles must exist before users can be assigned roles.
     * 3. SuperAdmin must be seeded last within auth module.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            CommitteeTypeSeeder::class,
            PositionSeeder::class,
            PostCategorySeeder::class,
            SuperAdminSeeder::class,
        ]);
    }
}

