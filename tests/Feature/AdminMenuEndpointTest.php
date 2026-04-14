<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminMenuEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_guest_cannot_access_admin_menu_endpoint(): void
    {
        $response = $this->getJson('/api/v1/admin/menu');

        $response->assertStatus(401);
    }

    public function test_member_role_is_forbidden_from_admin_menu_endpoint(): void
    {
        $user = User::factory()->create([
            'role_type' => 'member',
        ]);
        $user->assignRole('member');

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/admin/menu');

        $response
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'You are not authorized to view the admin menu.');
    }

    public function test_admin_gets_menu_without_superadmin_only_entries(): void
    {
        $user = User::factory()->create([
            'role_type' => 'admin',
        ]);
        $user->assignRole('admin');

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/admin/menu');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.role', 'admin')
            ->assertJsonPath('data.menu.0.key', 'main')
            ->assertJsonPath('data.menu.0.items.0.path', '/admin/dashboard')
            ->assertJsonMissing(['key' => 'roles-permissions'])
            ->assertJsonMissing(['key' => 'users-admins'])
            ->assertJsonMissing(['key' => 'security-settings']);
    }

    public function test_superadmin_gets_full_menu_with_system_entries(): void
    {
        $user = User::factory()->create([
            'role_type' => 'superadmin',
        ]);
        $user->assignRole('superadmin');

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/admin/menu');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.role', 'superadmin')
            ->assertJsonPath('data.menu.0.label', 'MAIN')
            ->assertJsonPath('data.menu.1.label', 'MEMBERSHIP')
            ->assertJsonPath('data.menu.4.items.0.badge_key', 'profile_update_requests_pending')
            ->assertJsonPath('data.menu.4.items.1.badge_key', 'unread_notifications')
            ->assertJsonFragment(['key' => 'roles-permissions'])
            ->assertJsonFragment(['key' => 'users-admins'])
            ->assertJsonFragment(['key' => 'security-settings']);
    }
}
