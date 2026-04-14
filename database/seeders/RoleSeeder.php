<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $superadmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin',      'guard_name' => 'web']);
        $member = Role::firstOrCreate(['name' => 'member',     'guard_name' => 'web']);

        // Assign all permissions to superadmin
        $allPermissions = Permission::all();
        $superadmin->syncPermissions($allPermissions);

        // Suggested baseline committee permissions for admin role
        $adminCommitteePermissions = [
            'committee.type.view',
            'committee.view',
            'committee.view.detail',
            'committee.create',
            'committee.update',
            'committee.status.update',
            'committee.summary.view',
        ];
        $admin->givePermissionTo($adminCommitteePermissions);

        // Suggested baseline position permissions for admin role
        $adminPositionPermissions = [
            'position.view',
            'position.view.detail',
            'position.create',
            'position.update',
            'position.status.update',
            'position.summary.view',
            'position.committee-type.map',
        ];
        $admin->givePermissionTo($adminPositionPermissions);

        $adminAssignmentPermissions = [
            'committee.member.assignment.view',
            'committee.member.assignment.view.detail',
            'committee.member.assignment.create',
            'committee.member.assignment.update',
            'committee.member.assignment.status.update',
            'committee.member.assignment.transfer',
            'committee.member.assignment.summary.view',
            'committee.office-bearer.view',
        ];
        $admin->givePermissionTo($adminAssignmentPermissions);

        $adminHierarchyPermissions = [
            'hierarchy.view',
            'hierarchy.view.detail',
            'hierarchy.create',
            'hierarchy.update',
            'hierarchy.status.update',
            'hierarchy.summary.view',
            'hierarchy.tree.view',
        ];
        $admin->givePermissionTo($adminHierarchyPermissions);

        $adminPostPermissions = [
            'post.category.view',
            'post.category.create',
            'post.category.update',
            'post.view',
            'post.view.detail',
            'post.create',
            'post.update',
            'post.publish',
            'post.unpublish',
            'post.archive',
            'post.feature',
            'post.summary.view',
            'post.public.view',
        ];
        $admin->givePermissionTo($adminPostPermissions);

        $adminNoticePermissions = [
            'notice.view',
            'notice.view.detail',
            'notice.create',
            'notice.update',
            'notice.publish',
            'notice.unpublish',
            'notice.archive',
            'notice.pin',
            'notice.attachment.manage',
            'notice.summary.view',
            'notice.public.view',
            'notice.member.view',
        ];
        $admin->givePermissionTo($adminNoticePermissions);

        $selfServicePermissions = [
            'self.profile.view',
            'self.profile.update',
            'self.profile.photo.update',
            'self.account.settings.update',
            'self.profile.request.create',
            'self.profile.request.view',
        ];

        $member->givePermissionTo($selfServicePermissions);
        $admin->givePermissionTo($selfServicePermissions);

        $adminProfileRequestReviewPermissions = [
            'profile.request.view',
            'profile.request.review',
            'profile.request.approve',
            'profile.request.reject',
        ];
        $admin->givePermissionTo($adminProfileRequestReviewPermissions);

        // Module 11 — Dashboard & Reporting permissions
        $adminDashboardReportPermissions = [
            'dashboard.view',
            'dashboard.admin.view',
            'report.view',
            'report.membership.view',
            'report.members.view',
            'report.committees.view',
            'report.assignments.view',
            'report.hierarchy.view',
            'report.posts.view',
            'report.notices.view',
            'report.profile-requests.view',
            'report.activity.view',
        ];
        $admin->givePermissionTo($adminDashboardReportPermissions);

        $memberDashboardPermissions = [
            'dashboard.view',
            'dashboard.member.view',
        ];
        $member->givePermissionTo($memberDashboardPermissions);

        $this->command->info('Roles seeded: superadmin, admin, member.');
        $this->command->info('Superadmin assigned '.count($allPermissions).' permissions.');
    }
}
