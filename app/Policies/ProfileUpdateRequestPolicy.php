<?php

namespace App\Policies;

use App\Models\ProfileUpdateRequest;
use App\Models\User;

class ProfileUpdateRequestPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        return null;
    }

    public function view(User $user, ProfileUpdateRequest $request): bool
    {
        return $request->user_id === $user->id || $user->can('profile.request.view');
    }

    public function create(User $user): bool
    {
        return $user->can('self.profile.request.create');
    }

    public function review(User $user): bool
    {
        return $user->can('profile.request.review');
    }
}
