<?php

namespace App\Policies;

use App\Models\MembershipApplication;
use App\Models\User;

class MembershipApplicationPolicy
{
    /**
     * Superadmin bypasses all policy checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('membership.application.view');
    }

    public function view(User $user, MembershipApplication $application): bool
    {
        return $user->can('membership.application.view');
    }

    public function delete(User $user, MembershipApplication $application): bool
    {
        return $user->can('membership.application.delete');
    }

    public function restore(User $user, MembershipApplication $application): bool
    {
        return $user->can('membership.application.restore');
    }
}
