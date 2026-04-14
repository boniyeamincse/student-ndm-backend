<?php

namespace App\Policies;

use App\Models\Position;
use App\Models\User;

class PositionPolicy
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
        return $user->can('position.view');
    }

    public function view(User $user, Position $position): bool
    {
        return $user->can('position.view.detail');
    }

    public function create(User $user): bool
    {
        return $user->can('position.create');
    }

    public function update(User $user, Position $position): bool
    {
        return $user->can('position.update');
    }

    public function updateStatus(User $user, Position $position): bool
    {
        return $user->can('position.status.update');
    }

    public function mapCommitteeTypes(User $user, Position $position): bool
    {
        return $user->can('position.committee-type.map');
    }

    public function delete(User $user, Position $position): bool
    {
        return $user->can('position.delete');
    }

    public function restore(User $user, Position $position): bool
    {
        return $user->can('position.restore');
    }
}
