<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

class PermissionService
{
    private const MODULE_LABELS = [
        'dashboard' => 'Dashboard',
        'membership' => 'Membership Applications',
        'member' => 'Members',
        'committee' => 'Committees',
        'position' => 'Positions',
        'hierarchy' => 'Reporting Hierarchy',
        'post' => 'Blog / News',
        'notice' => 'Notices',
        'self' => 'Self Service',
        'profile' => 'Profile Requests',
        'report' => 'Reports',
        'role' => 'Roles & Permissions',
        'permission' => 'Roles & Permissions',
        'user' => 'Users',
    ];

    /**
     * List all permissions.
     */
    public function list(): Collection
    {
        return Permission::orderBy('name')->get();
    }

    /**
     * Get permissions grouped by module.
     */
    public function getGrouped(): array
    {
        $permissions = $this->list();
        $grouped = [];

        foreach ($permissions as $permission) {
            $module = $this->extractModule($permission->name);
            
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }

            $grouped[$module][] = [
                'id'    => $permission->id,
                'name'  => $permission->name,
                'label' => $this->getLabel($permission->name),
                'module' => $module,
            ];
        }

        // Sort modules alphabetically
        ksort($grouped);

        return $grouped;
    }

    /**
     * Extract module from permission name (e.g., "user" from "user.view").
     */
    private function extractModule(string $permissionName): string
    {
        $parts = explode('.', $permissionName);
        $key = $parts[0] ?? 'system';

        return self::MODULE_LABELS[$key] ?? ucwords(str_replace(['.', '-'], ' ', $key));
    }

    /**
     * Generate human-readable label from permission name.
     */
    private function getLabel(string $permissionName): string
    {
        $parts = explode('.', $permissionName);
        $last = end($parts) ?: $permissionName;

        $actionMap = [
            'view' => 'View',
            'create' => 'Create',
            'update' => 'Update',
            'delete' => 'Delete',
            'assign' => 'Assign',
            'approve' => 'Approve',
            'reject' => 'Reject',
            'review' => 'Review',
            'restore' => 'Restore',
            'publish' => 'Publish',
            'unpublish' => 'Unpublish',
            'archive' => 'Archive',
            'hold' => 'Hold',
        ];

        if (isset($actionMap[$last])) {
            $subject = implode(' ', array_slice($parts, 0, -1));
            $subject = str_replace(['.', '-'], ' ', $subject);
            return trim($actionMap[$last] . ' ' . ucwords($subject));
        }

        return ucwords(str_replace(['.', '_', '-'], ' ', $permissionName));
    }
}
