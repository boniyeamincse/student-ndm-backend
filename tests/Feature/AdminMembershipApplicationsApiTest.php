<?php

namespace Tests\Feature;

use App\Enum\ApplicationStatus;
use App\Models\MembershipApplication;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminMembershipApplicationsApiTest extends TestCase
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

    public function test_admin_can_list_applications(): void
    {
        $admin = $this->makeAdminWithPermissions([
            'membership.application.view',
        ]);

        $this->makeApplication(['status' => ApplicationStatus::Pending->value]);
        $this->makeApplication(['status' => ApplicationStatus::Approved->value]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/admin/membership-applications');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_admin_can_filter_by_pending_and_approved(): void
    {
        $admin = $this->makeAdminWithPermissions(['membership.application.view']);

        $this->makeApplication(['status' => ApplicationStatus::Pending->value]);
        $this->makeApplication(['status' => ApplicationStatus::Approved->value]);

        Sanctum::actingAs($admin);

        $pendingResponse = $this->getJson('/api/v1/admin/membership-applications?status=pending');
        $pendingResponse->assertOk()->assertJsonPath('meta.total', 1);

        $approvedResponse = $this->getJson('/api/v1/admin/membership-applications?status=approved');
        $approvedResponse->assertOk()->assertJsonPath('meta.total', 1);
    }

    public function test_admin_can_search_by_name_email_mobile_and_application_number(): void
    {
        $admin = $this->makeAdminWithPermissions(['membership.application.view']);

        $app = $this->makeApplication([
            'full_name' => 'Search Target',
            'email' => 'target@app.test',
            'mobile' => '01700009999',
            'application_no' => 'SMN-SEARCH-0001',
        ]);

        $this->makeApplication(['full_name' => 'Other Person']);

        Sanctum::actingAs($admin);

        foreach (['Search Target', 'target@app.test', '01700009999', 'SMN-SEARCH-0001'] as $needle) {
            $response = $this->getJson('/api/v1/admin/membership-applications?search='.urlencode($needle));
            $response
                ->assertOk()
                ->assertJsonPath('meta.total', 1)
                ->assertJsonPath('data.0.id', $app->id);
        }
    }

    public function test_admin_can_view_application_detail(): void
    {
        $admin = $this->makeAdminWithPermissions(['membership.application.view']);
        $app = $this->makeApplication();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/admin/membership-applications/'.$app->id);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $app->id)
            ->assertJsonPath('data.application_no', $app->application_no);
    }

    public function test_admin_can_mark_application_under_review(): void
    {
        $admin = $this->makeAdminWithPermissions(['membership.application.review']);
        $app = $this->makeApplication(['status' => ApplicationStatus::Pending->value]);

        Sanctum::actingAs($admin);

        $response = $this->putJson('/api/v1/admin/membership-applications/'.$app->id.'/review', [
            'remarks' => 'Initial review started.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.status', ApplicationStatus::UnderReview->value);

        $this->assertDatabaseHas('membership_applications', [
            'id' => $app->id,
            'status' => ApplicationStatus::UnderReview->value,
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_admin_can_approve_application_and_member_user_are_created(): void
    {
        $admin = $this->makeAdminWithPermissions(['membership.application.approve']);
        $app = $this->makeApplication(['status' => ApplicationStatus::Pending->value]);

        Sanctum::actingAs($admin);

        $response = $this->putJson('/api/v1/admin/membership-applications/'.$app->id.'/approve', [
            'remarks' => 'Approved by test.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.application.status', ApplicationStatus::Approved->value)
            ->assertJsonPath('data.member.membership_application_id', $app->id);

        $this->assertDatabaseHas('membership_applications', [
            'id' => $app->id,
            'status' => ApplicationStatus::Approved->value,
            'approved_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => $app->email,
            'role_type' => 'member',
        ]);

        $this->assertDatabaseHas('members', [
            'membership_application_id' => $app->id,
            'email' => $app->email,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_reject_application_with_reason(): void
    {
        $admin = $this->makeAdminWithPermissions(['membership.application.reject']);
        $app = $this->makeApplication(['status' => ApplicationStatus::UnderReview->value]);

        Sanctum::actingAs($admin);

        $response = $this->putJson('/api/v1/admin/membership-applications/'.$app->id.'/reject', [
            'rejection_reason' => 'Documents mismatch.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.status', ApplicationStatus::Rejected->value)
            ->assertJsonPath('data.rejection_reason', 'Documents mismatch.');

        $this->assertDatabaseHas('membership_applications', [
            'id' => $app->id,
            'status' => ApplicationStatus::Rejected->value,
            'rejection_reason' => 'Documents mismatch.',
        ]);
    }

    public function test_admin_can_hold_application(): void
    {
        $admin = $this->makeAdminWithPermissions(['membership.application.hold']);
        $app = $this->makeApplication(['status' => ApplicationStatus::Pending->value]);

        Sanctum::actingAs($admin);

        $response = $this->putJson('/api/v1/admin/membership-applications/'.$app->id.'/hold', [
            'remarks' => 'Need additional verification.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.status', ApplicationStatus::OnHold->value);

        $this->assertDatabaseHas('membership_applications', [
            'id' => $app->id,
            'status' => ApplicationStatus::OnHold->value,
            'remarks' => 'Need additional verification.',
        ]);
    }

    public function test_invalid_state_transition_is_blocked_when_rejecting_approved_application(): void
    {
        $admin = $this->makeAdminWithPermissions(['membership.application.reject']);
        $app = $this->makeApplication(['status' => ApplicationStatus::Approved->value]);

        Sanctum::actingAs($admin);

        $response = $this->putJson('/api/v1/admin/membership-applications/'.$app->id.'/reject', [
            'rejection_reason' => 'Should fail.',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_unauthorized_member_cannot_access_admin_application_routes(): void
    {
        $member = User::factory()->create(['role_type' => 'member']);
        $member->assignRole('member');

        Sanctum::actingAs($member);

        $response = $this->getJson('/api/v1/admin/membership-applications');

        $response->assertStatus(403);
    }

    private function makeAdminWithPermissions(array $permissions): User
    {
        $user = User::factory()->create(['role_type' => 'admin']);
        $user->assignRole('admin');
        $user->givePermissionTo($permissions);

        return $user;
    }

    private function makeApplication(array $overrides = []): MembershipApplication
    {
        $index = $this->sequence++;

        return MembershipApplication::create(array_merge([
            'application_no' => sprintf('SMN-TEST-%04d', $index),
            'full_name' => 'Applicant '.$index,
            'email' => 'applicant'.$index.'@example.test',
            'mobile' => '017'.str_pad((string) $index, 8, '0', STR_PAD_LEFT),
            'district_name' => 'Dhaka',
            'division_name' => 'Dhaka',
            'status' => ApplicationStatus::Pending->value,
        ], $overrides));
    }
}
