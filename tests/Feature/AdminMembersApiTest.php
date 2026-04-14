<?php

namespace Tests\Feature;

use App\Enum\MemberStatus;
use App\Enum\UserStatus;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminMembersApiTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_admin_can_list_members_and_filter_by_statuses(): void
    {
        $admin = $this->makeAdminWithPermissions([
            'member.view',
        ]);

        $this->makeMember(['status' => MemberStatus::Active->value]);
        $this->makeMember(['status' => MemberStatus::Inactive->value]);
        $this->makeMember(['status' => MemberStatus::Suspended->value]);

        Sanctum::actingAs($admin);

        $all = $this->getJson('/api/v1/admin/members');
        $all->assertOk()->assertJsonPath('meta.total', 3);

        $active = $this->getJson('/api/v1/admin/members?status=active');
        $active->assertOk()->assertJsonPath('meta.total', 1);

        $inactive = $this->getJson('/api/v1/admin/members?status=inactive');
        $inactive->assertOk()->assertJsonPath('meta.total', 1);

        $suspended = $this->getJson('/api/v1/admin/members?status=suspended');
        $suspended->assertOk()->assertJsonPath('meta.total', 1);
    }

    public function test_admin_can_view_member_detail(): void
    {
        $admin = $this->makeAdminWithPermissions(['member.view.detail']);
        $member = $this->makeMember();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/admin/members/'.$member->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $member->id)
            ->assertJsonPath('data.member_no', $member->member_no);
    }

    public function test_admin_can_update_member_safe_fields(): void
    {
        $admin = $this->makeAdminWithPermissions(['member.update']);
        $member = $this->makeMember();

        Sanctum::actingAs($admin);

        $response = $this->putJson('/api/v1/admin/members/'.$member->id, [
            'full_name' => 'Updated Member Name',
            'email' => 'updated-member@example.test',
            'mobile' => '01800001234',
            'educational_institution' => 'NDM College',
            'district_name' => 'Gazipur',
            'division_name' => 'Dhaka',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.full_name', 'Updated Member Name')
            ->assertJsonPath('data.email', 'updated-member@example.test');

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'full_name' => 'Updated Member Name',
            'email' => 'updated-member@example.test',
            'mobile' => '01800001234',
            'educational_institution' => 'NDM College',
        ]);
    }

    public function test_admin_can_update_member_status(): void
    {
        $admin = $this->makeAdminWithPermissions(['member.update']);
        $member = $this->makeMember(['status' => MemberStatus::Active->value]);

        Sanctum::actingAs($admin);

        $response = $this->patchJson('/api/v1/admin/members/'.$member->id.'/status', [
            'status' => MemberStatus::Inactive->value,
            'note' => 'Temporarily inactive.',
            'revoke_access' => false,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.status', MemberStatus::Inactive->value)
            ->assertJsonPath('data.status_note', 'Temporarily inactive.');

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'status' => MemberStatus::Inactive->value,
            'status_note' => 'Temporarily inactive.',
        ]);
    }

    public function test_linked_user_status_updates_when_revoke_access_is_true(): void
    {
        $admin = $this->makeAdminWithPermissions(['member.update']);
        $linkedUser = User::factory()->create([
            'role_type' => 'member',
            'status' => UserStatus::Active->value,
        ]);
        $linkedUser->assignRole('member');

        $member = $this->makeMember([
            'user_id' => $linkedUser->id,
            'status' => MemberStatus::Active->value,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->patchJson('/api/v1/admin/members/'.$member->id.'/status', [
            'status' => MemberStatus::Suspended->value,
            'note' => 'Policy violation',
            'revoke_access' => true,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.status', MemberStatus::Suspended->value);

        $this->assertDatabaseHas('users', [
            'id' => $linkedUser->id,
            'status' => UserStatus::Inactive->value,
        ]);
    }

    public function test_unauthorized_member_cannot_access_admin_member_routes(): void
    {
        $memberUser = User::factory()->create(['role_type' => 'member']);
        $memberUser->assignRole('member');

        Sanctum::actingAs($memberUser);

        $response = $this->getJson('/api/v1/admin/members');

        $response->assertStatus(403);
    }

    private function makeAdminWithPermissions(array $permissions): User
    {
        $user = User::factory()->create(['role_type' => 'admin']);
        $user->assignRole('admin');
        $user->givePermissionTo($permissions);

        return $user;
    }

    private function makeMember(array $overrides = []): Member
    {
        $index = $this->sequence++;

        return Member::create(array_merge([
            'member_no' => sprintf('MEM-TEST-%04d', $index),
            'full_name' => 'Member '.$index,
            'email' => 'member'.$index.'@example.test',
            'mobile' => '019'.str_pad((string) $index, 8, '0', STR_PAD_LEFT),
            'status' => MemberStatus::Active->value,
            'joined_at' => now()->subDays($index),
        ], $overrides));
    }
}
