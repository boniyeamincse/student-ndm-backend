<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserRoleService
{
    /**
     * Sync roles to user with safety checks.
     */
    public function syncUserRoles(User $targetUser, array $roleIds, User $actor): User
    {
        return DB::transaction(function () use ($targetUser, $roleIds, $actor) {
            $roleNames = Role::query()->whereIn('id', $roleIds)->pluck('name')->values();

            $this->guardAgainstUnsafeSuperadminChange($targetUser, $roleNames->all(), $actor);

            $targetUser->syncRoles($roleNames->all());

            return $targetUser->fresh('roles');
        });
    }

    /**
     * Prevent deleting superadmin role from the last superadmin and avoid self-lockout.
     */
    private function guardAgainstUnsafeSuperadminChange(User $targetUser, array $newRoleNames, User $actor): void
    {
        $targetHasSuperadmin = $targetUser->hasRole('superadmin');
        $willKeepSuperadmin = in_array('superadmin', $newRoleNames, true);

        if ($targetHasSuperadmin && ! $willKeepSuperadmin) {
            $superadminCount = User::role('superadmin')->count();

            if ($superadminCount <= 1) {
                abort(422, 'Cannot remove superadmin from the last superadmin account.');
            }

            if ((int) $targetUser->id === (int) $actor->id) {
                abort(422, 'Cannot remove your own superadmin role.');
            }
        }
    }
}
