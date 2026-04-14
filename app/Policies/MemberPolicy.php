<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;

class MemberPolicy
{
    /**
     * Superadmin bypasses all policy checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('member.view');
    }

    public function view(User $user, Member $member): bool
    {
        return $user->hasPermissionTo('member.view.detail');
    }

    public function update(User $user, Member $member): bool
    {
        return $user->hasPermissionTo('member.update');
    }

    public function delete(User $user, Member $member): bool
    {
        return $user->hasPermissionTo('member.delete');
    }

    public function restore(User $user, Member $member): bool
    {
        return $user->hasPermissionTo('member.restore');
    }
}
