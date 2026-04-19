<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminRolesPermissionsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_superadmin_can_list_roles(): void
    {
        $user = User::factory()->create(['role_type' => 'superadmin']);
        $user->assignRole('superadmin');

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/admin/roles');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Roles retrieved successfully.');
    }

    public function test_superadmin_can_create_role(): void
    {
        $user = User::factory()->create(['role_type' => 'superadmin']);
        $user->assignRole('superadmin');
        Sanctum::actingAs($user);

        $permission = Permission::where('name', 'report.view')->firstOrFail();

        $response = $this->postJson('/api/v1/admin/roles', [
            'name' => 'district_admin',
            'permissions' => [$permission->id],
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('roles', ['name' => 'district_admin']);
    }

    public function test_member_cannot_access_roles_list(): void
    {
        $user = User::factory()->create(['role_type' => 'member']);
        $user->assignRole('member');
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/admin/roles');

        $response->assertStatus(403);
    }

    public function test_protected_role_cannot_be_deleted(): void
    {
        $user = User::factory()->create(['role_type' => 'superadmin']);
        $user->assignRole('superadmin');
        Sanctum::actingAs($user);

        $superadminRole = Role::where('name', 'superadmin')->firstOrFail();

        $response = $this->deleteJson('/api/v1/admin/roles/' . $superadminRole->id);

        $response->assertStatus(422);
    }

    public function test_permissions_grouped_endpoint_works(): void
    {
        $user = User::factory()->create(['role_type' => 'superadmin']);
        $user->assignRole('superadmin');
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/admin/permissions/grouped');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'data']);
    }
}
