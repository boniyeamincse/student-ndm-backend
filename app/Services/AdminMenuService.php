<?php

namespace App\Services;

use App\Models\User;

class AdminMenuService
{
    /**
     * Build a role-aware admin sidebar payload for frontend rendering.
     */
    public function forUser(User $user): array
    {
        $menu = $this->baseMenu();

        return [
            'role' => $user->hasRole('superadmin') ? 'superadmin' : 'admin',
            'menu' => $this->filterByRole($menu, $user),
        ];
    }

    /**
     * Full admin menu tree with permission mapping metadata.
     */
    private function baseMenu(): array
    {
        return [
            [
                'key' => 'main',
                'label' => 'MAIN',
                'items' => [
                    ['key' => 'dashboard', 'label' => 'Dashboard', 'path' => '/admin/dashboard', 'required_permissions' => ['dashboard.view']],
                ],
            ],
            [
                'key' => 'membership',
                'label' => 'MEMBERSHIP',
                'collapsible' => true,
                'items' => [
                    [
                        'key' => 'membership-applications',
                        'label' => 'Membership Applications',
                        'icon' => 'file-user',
                        'badge_key' => 'pending_applications',
                        'children' => [
                            ['key' => 'all-applications', 'label' => 'All Applications', 'path' => '/admin/membership-applications', 'required_permissions' => ['membership.application.view']],
                            ['key' => 'pending-applications', 'label' => 'Pending Applications', 'path' => '/admin/membership-applications?status=pending', 'required_permissions' => ['membership.application.view']],
                            ['key' => 'under-review-applications', 'label' => 'Under Review', 'path' => '/admin/membership-applications?status=under_review', 'required_permissions' => ['membership.application.review']],
                            ['key' => 'approved-applications', 'label' => 'Approved Applications', 'path' => '/admin/membership-applications?status=approved', 'required_permissions' => ['membership.application.approve']],
                            ['key' => 'rejected-applications', 'label' => 'Rejected Applications', 'path' => '/admin/membership-applications?status=rejected', 'required_permissions' => ['membership.application.reject']],
                            ['key' => 'on-hold-applications', 'label' => 'On Hold', 'path' => '/admin/membership-applications?status=on_hold', 'required_permissions' => ['membership.application.hold']],
                        ],
                    ],
                    [
                        'key' => 'members',
                        'label' => 'Members',
                        'icon' => 'users',
                        'children' => [
                            ['key' => 'all-members', 'label' => 'All Members', 'path' => '/admin/members', 'required_permissions' => ['member.view']],
                            ['key' => 'active-members', 'label' => 'Active Members', 'path' => '/admin/members?status=active', 'required_permissions' => ['member.view']],
                            ['key' => 'inactive-members', 'label' => 'Inactive Members', 'path' => '/admin/members?status=inactive', 'required_permissions' => ['member.view']],
                            ['key' => 'suspended-members', 'label' => 'Suspended Members', 'path' => '/admin/members?status=suspended', 'required_permissions' => ['member.view']],
                            ['key' => 'leadership-members', 'label' => 'Leadership Members', 'path' => '/admin/members?leadership=1', 'required_permissions' => ['member.view']],
                            ['key' => 'new-members', 'label' => 'New Members', 'path' => '/admin/members?sort=created_desc', 'required_permissions' => ['member.view']],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'organization',
                'label' => 'ORGANIZATION',
                'collapsible' => true,
                'items' => [
                    [
                        'key' => 'committees',
                        'label' => 'Committees',
                        'icon' => 'building',
                        'children' => [
                            ['key' => 'all-committees', 'label' => 'All Committees', 'path' => '/admin/committees', 'required_permissions' => ['committee.view']],
                            ['key' => 'central-committee', 'label' => 'Central Committee', 'path' => '/admin/committees?scope=central', 'required_permissions' => ['committee.view']],
                            ['key' => 'division-committee', 'label' => 'Division Committee', 'path' => '/admin/committees?scope=division', 'required_permissions' => ['committee.view']],
                            ['key' => 'district-committee', 'label' => 'District Committee', 'path' => '/admin/committees?scope=district', 'required_permissions' => ['committee.view']],
                            ['key' => 'upazila-committee', 'label' => 'Upazila Committee', 'path' => '/admin/committees?scope=upazila', 'required_permissions' => ['committee.view']],
                            ['key' => 'union-committee', 'label' => 'Union Committee', 'path' => '/admin/committees?scope=union', 'required_permissions' => ['committee.view']],
                            ['key' => 'archived-committees', 'label' => 'Archived Committees', 'path' => '/admin/committees?status=archived', 'required_permissions' => ['committee.view']],
                        ],
                    ],
                    [
                        'key' => 'committee-types',
                        'label' => 'Committee Types',
                        'children' => [
                            ['key' => 'all-committee-types', 'label' => 'All Types', 'path' => '/admin/committee-types', 'required_permissions' => ['committee.type.view']],
                            ['key' => 'add-committee-type', 'label' => 'Add Type', 'path' => '/admin/committee-types/create', 'required_permissions' => ['committee.type.create']],
                        ],
                    ],
                    [
                        'key' => 'positions',
                        'label' => 'Positions',
                        'children' => [
                            ['key' => 'all-positions', 'label' => 'All Positions', 'path' => '/admin/positions', 'required_permissions' => ['position.view']],
                            ['key' => 'leadership-positions', 'label' => 'Leadership Positions', 'path' => '/admin/positions?category=leadership', 'required_permissions' => ['position.view']],
                            ['key' => 'general-positions', 'label' => 'General Positions', 'path' => '/admin/positions?category=general', 'required_permissions' => ['position.view']],
                        ],
                    ],
                    [
                        'key' => 'committee-assignments',
                        'label' => 'Committee Assignments',
                        'children' => [
                            ['key' => 'all-assignments', 'label' => 'All Assignments', 'path' => '/admin/committee-member-assignments', 'required_permissions' => ['committee.member.assignment.view']],
                            ['key' => 'active-assignments', 'label' => 'Active Assignments', 'path' => '/admin/committee-member-assignments?status=active', 'required_permissions' => ['committee.member.assignment.view']],
                            ['key' => 'office-bearers', 'label' => 'Office Bearers', 'path' => '/admin/committee-member-assignments?type=office_bearer', 'required_permissions' => ['committee.office-bearer.view']],
                            ['key' => 'general-members', 'label' => 'General Members', 'path' => '/admin/committee-member-assignments?type=member', 'required_permissions' => ['committee.member.assignment.view']],
                        ],
                    ],
                    [
                        'key' => 'reporting-hierarchy',
                        'label' => 'Reporting Hierarchy',
                        'children' => [
                            ['key' => 'organization-tree', 'label' => 'Organization Tree', 'path' => '/admin/hierarchy/tree', 'required_permissions' => ['hierarchy.tree.view']],
                            ['key' => 'leader-subordinates', 'label' => 'Leader -> Subordinates', 'path' => '/admin/hierarchy/leader-subordinates', 'required_permissions' => ['hierarchy.view.detail']],
                            ['key' => 'hierarchy-settings', 'label' => 'Hierarchy Settings', 'path' => '/admin/hierarchy/settings', 'required_permissions' => ['hierarchy.update']],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'content',
                'label' => 'CONTENT',
                'collapsible' => true,
                'items' => [
                    [
                        'key' => 'blog-news',
                        'label' => 'Blog / News',
                        'icon' => 'newspaper',
                        'children' => [
                            ['key' => 'all-posts', 'label' => 'All Posts', 'path' => '/admin/posts', 'required_permissions' => ['post.view']],
                            ['key' => 'create-post', 'label' => 'Create Post', 'path' => '/admin/posts/create', 'required_permissions' => ['post.create']],
                            ['key' => 'draft-posts', 'label' => 'Drafts', 'path' => '/admin/posts?status=draft', 'required_permissions' => ['post.view']],
                            ['key' => 'published-posts', 'label' => 'Published', 'path' => '/admin/posts?status=published', 'required_permissions' => ['post.view']],
                            ['key' => 'archived-posts', 'label' => 'Archived', 'path' => '/admin/posts?status=archived', 'required_permissions' => ['post.view']],
                            ['key' => 'post-categories', 'label' => 'Categories', 'path' => '/admin/post-categories', 'required_permissions' => ['post.category.view']],
                        ],
                    ],
                    [
                        'key' => 'notices',
                        'label' => 'Notices',
                        'icon' => 'bell',
                        'children' => [
                            ['key' => 'all-notices', 'label' => 'All Notices', 'path' => '/admin/notices', 'required_permissions' => ['notice.view']],
                            ['key' => 'create-notice', 'label' => 'Create Notice', 'path' => '/admin/notices/create', 'required_permissions' => ['notice.create']],
                            ['key' => 'draft-notices', 'label' => 'Draft Notices', 'path' => '/admin/notices?status=draft', 'required_permissions' => ['notice.view']],
                            ['key' => 'published-notices', 'label' => 'Published Notices', 'path' => '/admin/notices?status=published', 'required_permissions' => ['notice.view']],
                            ['key' => 'pinned-notices', 'label' => 'Pinned Notices', 'path' => '/admin/notices?pinned=1', 'required_permissions' => ['notice.pin']],
                            ['key' => 'expired-notices', 'label' => 'Expired Notices', 'path' => '/admin/notices?status=expired', 'required_permissions' => ['notice.view']],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'requests-communication',
                'label' => 'REQUESTS & COMMUNICATION',
                'items' => [
                    [
                        'key' => 'profile-update-requests',
                        'label' => 'Profile Update Requests',
                        'icon' => 'user-pen',
                        'badge_key' => 'profile_update_requests_pending',
                        'children' => [
                            ['key' => 'all-profile-requests', 'label' => 'All Requests', 'path' => '/admin/profile-update-requests', 'required_permissions' => ['profile.request.view']],
                            ['key' => 'pending-profile-requests', 'label' => 'Pending Requests', 'path' => '/admin/profile-update-requests?status=pending', 'required_permissions' => ['profile.request.review']],
                            ['key' => 'approved-profile-requests', 'label' => 'Approved Requests', 'path' => '/admin/profile-update-requests?status=approved', 'required_permissions' => ['profile.request.approve']],
                            ['key' => 'rejected-profile-requests', 'label' => 'Rejected Requests', 'path' => '/admin/profile-update-requests?status=rejected', 'required_permissions' => ['profile.request.reject']],
                        ],
                    ],
                    [
                        'key' => 'notifications',
                        'label' => 'Notifications',
                        'icon' => 'send',
                        'badge_key' => 'unread_notifications',
                        'future_ready' => true,
                        'children' => [
                            ['key' => 'send-notification', 'label' => 'Send Notification', 'path' => '/admin/notifications/send'],
                            ['key' => 'notification-logs', 'label' => 'Notification Logs', 'path' => '/admin/notifications/logs'],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'reports-analytics',
                'label' => 'REPORTS & ANALYTICS',
                'collapsible' => true,
                'items' => [
                    [
                        'key' => 'reports',
                        'label' => 'Reports',
                        'icon' => 'chart-column',
                        'children' => [
                            ['key' => 'overview-report', 'label' => 'Overview Report', 'path' => '/admin/reports/overview', 'required_permissions' => ['report.view']],
                            ['key' => 'membership-report', 'label' => 'Membership Report', 'path' => '/admin/reports/membership-applications', 'required_permissions' => ['report.membership.view']],
                            ['key' => 'committee-report', 'label' => 'Committee Report', 'path' => '/admin/reports/committees', 'required_permissions' => ['report.committees.view']],
                            ['key' => 'assignment-report', 'label' => 'Assignment Report', 'path' => '/admin/reports/committee-assignments', 'required_permissions' => ['report.assignments.view']],
                            ['key' => 'content-report', 'label' => 'Content Report', 'path' => '/admin/reports/posts', 'required_permissions' => ['report.posts.view']],
                            ['key' => 'notice-report', 'label' => 'Notice Report', 'path' => '/admin/reports/notices', 'required_permissions' => ['report.notices.view']],
                            ['key' => 'activity-report', 'label' => 'Activity Report', 'path' => '/admin/reports/activity-summary', 'required_permissions' => ['report.activity.view']],
                        ],
                    ],
                    [
                        'key' => 'dashboard-analytics',
                        'label' => 'Dashboard Analytics',
                        'icon' => 'line-chart',
                        'children' => [
                            ['key' => 'growth-charts', 'label' => 'Growth Charts', 'path' => '/admin/dashboard/charts', 'required_permissions' => ['dashboard.admin.view']],
                            ['key' => 'performance-metrics', 'label' => 'Performance Metrics', 'path' => '/admin/dashboard/stats', 'required_permissions' => ['dashboard.admin.view']],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'system',
                'label' => 'SYSTEM',
                'items' => [
                    [
                        'key' => 'settings',
                        'label' => 'Settings',
                        'icon' => 'settings',
                        'children' => [
                            ['key' => 'general-settings', 'label' => 'General Settings', 'path' => '/admin/settings/general'],
                            ['key' => 'organization-settings', 'label' => 'Organization Settings', 'path' => '/admin/settings/organization'],
                            ['key' => 'email-settings', 'label' => 'Email Settings', 'path' => '/admin/settings/email'],
                            ['key' => 'notification-settings', 'label' => 'Notification Settings', 'path' => '/admin/settings/notifications'],
                            ['key' => 'security-settings', 'label' => 'Security Settings', 'path' => '/admin/settings/security', 'visible_for_roles' => ['superadmin']],
                        ],
                    ],
                    [
                        'key' => 'roles-permissions',
                        'label' => 'Roles & Permissions',
                        'icon' => 'shield',
                        'visible_for_roles' => ['superadmin'],
                        'children' => [
                            ['key' => 'roles', 'label' => 'Roles', 'path' => '/admin/roles'],
                            ['key' => 'permissions', 'label' => 'Permissions', 'path' => '/admin/permissions'],
                        ],
                    ],
                    [
                        'key' => 'users-admins',
                        'label' => 'Users (Admins)',
                        'icon' => 'user-cog',
                        'visible_for_roles' => ['superadmin'],
                        'children' => [
                            ['key' => 'admin-users', 'label' => 'Admin Users', 'path' => '/admin/users/admins'],
                            ['key' => 'add-admin', 'label' => 'Add Admin', 'path' => '/admin/users/admins/create'],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'user-utility',
                'label' => 'USER',
                'items' => [
                    ['key' => 'my-profile', 'label' => 'My Profile', 'path' => '/admin/me/profile', 'required_permissions' => ['self.profile.view']],
                    ['key' => 'account-settings', 'label' => 'Account Settings', 'path' => '/admin/me/account-settings', 'required_permissions' => ['self.account.settings.update']],
                    ['key' => 'change-password', 'label' => 'Change Password', 'path' => '/admin/me/change-password'],
                    ['key' => 'logout', 'label' => 'Logout', 'path' => '/logout'],
                ],
            ],
        ];
    }

    /**
     * Remove superadmin-only menu entries for non-superadmin users.
     */
    private function filterByRole(array $sections, User $user): array
    {
        $role = $user->hasRole('superadmin') ? 'superadmin' : 'admin';

        $filterNodes = function (array $nodes) use (&$filterNodes, $role): array {
            $filtered = [];

            foreach ($nodes as $node) {
                $allowedRoles = $node['visible_for_roles'] ?? ['superadmin', 'admin'];

                if (! in_array($role, $allowedRoles, true)) {
                    continue;
                }

                if (isset($node['children']) && is_array($node['children'])) {
                    $node['children'] = $filterNodes($node['children']);

                    if ($node['children'] === []) {
                        continue;
                    }
                }

                if (isset($node['items']) && is_array($node['items'])) {
                    $node['items'] = $filterNodes($node['items']);

                    if ($node['items'] === []) {
                        continue;
                    }
                }

                $filtered[] = $node;
            }

            return $filtered;
        };

        return $filterNodes($sections);
    }
}
