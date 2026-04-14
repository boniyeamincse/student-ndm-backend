<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * All system permissions grouped by module.
     * Add new permissions here as new modules are developed.
     */
    private const PERMISSIONS = [
        // User management
        'user.view',
        'user.create',
        'user.update',
        'user.delete',

        // Role management
        'role.view',
        'role.assign',

        // Profile
        'profile.view',
        'profile.update',

        // Module 02 — Membership application management
        'membership.application.view',
        'membership.application.create',
        'membership.application.review',
        'membership.application.approve',
        'membership.application.reject',
        'membership.application.hold',
        'membership.application.delete',
        'membership.application.restore',

        // Module 02 — Member management
        'member.view',
        'member.create',
        'member.update',
        'member.status.update',

        // Module 03 — Extended member management
        'member.view.detail',
        'member.delete',
        'member.restore',
        'member.export',
        'member.summary.view',

        // Module 04 — Committee type management
        'committee.type.view',
        'committee.type.create',
        'committee.type.update',
        'committee.type.delete',

        // Module 04 — Committee management
        'committee.view',
        'committee.view.detail',
        'committee.create',
        'committee.update',
        'committee.status.update',
        'committee.delete',
        'committee.restore',
        'committee.summary.view',

        // Module 05 — Position management
        'position.view',
        'position.view.detail',
        'position.create',
        'position.update',
        'position.status.update',
        'position.delete',
        'position.restore',
        'position.summary.view',
        'position.committee-type.map',

        // Module 06 — Committee member assignment management
        'committee.member.assignment.view',
        'committee.member.assignment.view.detail',
        'committee.member.assignment.create',
        'committee.member.assignment.update',
        'committee.member.assignment.status.update',
        'committee.member.assignment.delete',
        'committee.member.assignment.restore',
        'committee.member.assignment.transfer',
        'committee.member.assignment.summary.view',
        'committee.office-bearer.view',

        // Module 07 — Member reporting hierarchy management
        'hierarchy.view',
        'hierarchy.view.detail',
        'hierarchy.create',
        'hierarchy.update',
        'hierarchy.status.update',
        'hierarchy.delete',
        'hierarchy.restore',
        'hierarchy.summary.view',
        'hierarchy.tree.view',

        // Module 08 — Blog/News publishing management
        'post.category.view',
        'post.category.create',
        'post.category.update',
        'post.category.delete',
        'post.category.restore',
        'post.view',
        'post.view.detail',
        'post.create',
        'post.update',
        'post.delete',
        'post.restore',
        'post.publish',
        'post.unpublish',
        'post.archive',
        'post.feature',
        'post.summary.view',
        'post.public.view',

        // Module 09 — Notice management
        'notice.view',
        'notice.view.detail',
        'notice.create',
        'notice.update',
        'notice.delete',
        'notice.restore',
        'notice.publish',
        'notice.unpublish',
        'notice.archive',
        'notice.pin',
        'notice.attachment.manage',
        'notice.summary.view',
        'notice.public.view',
        'notice.member.view',

        // Module 10 — Profile self-service & member account management
        'self.profile.view',
        'self.profile.update',
        'self.profile.photo.update',
        'self.account.settings.update',
        'self.profile.request.create',
        'self.profile.request.view',
        'profile.request.view',
        'profile.request.review',
        'profile.request.approve',
        'profile.request.reject',

        // Module 11 — Dashboard & Reporting
        'dashboard.view',
        'dashboard.superadmin.view',
        'dashboard.admin.view',
        'dashboard.member.view',
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

    public function run(): void
    {
        // Use web guard — Sanctum resolves against it for API token auth
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $this->command->info('Permissions seeded: '.count(self::PERMISSIONS).' permissions.');
    }
}
