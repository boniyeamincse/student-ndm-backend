<?php

namespace App\Policies;

use App\Models\Committee;
use App\Models\User;

class CommitteePolicy
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
        return $user->can('committee.view');
    }

    public function view(User $user, Committee $committee): bool
    {
        return $user->can('committee.view.detail');
    }

    public function create(User $user): bool
    {
        return $user->can('committee.create');
    }

    public function update(User $user, Committee $committee): bool
    {
        return $user->can('committee.update');
    }

    public function updateStatus(User $user, Committee $committee): bool
    {
        return $user->can('committee.status.update');
    }

    public function delete(User $user, Committee $committee): bool
    {
        return $user->can('committee.delete');
    }

    public function restore(User $user, Committee $committee): bool
    {
        return $user->can('committee.restore');
    }
}
