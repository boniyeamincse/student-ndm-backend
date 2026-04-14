<?php

namespace App\Policies;

use App\Models\CommitteeType;
use App\Models\User;

class CommitteeTypePolicy
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
        return $user->can('committee.type.view');
    }

    public function view(User $user, CommitteeType $committeeType): bool
    {
        return $user->can('committee.type.view');
    }

    public function create(User $user): bool
    {
        return $user->can('committee.type.create');
    }

    public function update(User $user, CommitteeType $committeeType): bool
    {
        return $user->can('committee.type.update');
    }

    public function delete(User $user, CommitteeType $committeeType): bool
    {
        return $user->can('committee.type.delete');
    }

    public function restore(User $user, CommitteeType $committeeType): bool
    {
        return $user->can('committee.type.delete');
    }
}
