<?php

namespace App\Policies;

use App\Models\MemberReportingRelation;
use App\Models\User;

class MemberReportingRelationPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('hierarchy.view');
    }

    public function view(User $user, MemberReportingRelation $relation): bool
    {
        return $user->can('hierarchy.view.detail');
    }

    public function create(User $user): bool
    {
        return $user->can('hierarchy.create');
    }

    public function update(User $user, MemberReportingRelation $relation): bool
    {
        return $user->can('hierarchy.update');
    }

    public function updateStatus(User $user, MemberReportingRelation $relation): bool
    {
        return $user->can('hierarchy.status.update');
    }

    public function delete(User $user, MemberReportingRelation $relation): bool
    {
        return $user->can('hierarchy.delete');
    }

    public function restore(User $user, MemberReportingRelation $relation): bool
    {
        return $user->can('hierarchy.restore');
    }
}
