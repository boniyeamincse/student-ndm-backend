<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;

trait EnsuresMemberAccess
{
    protected function ensureMemberAccess(): ?JsonResponse
    {
        $user = auth()->user();

        if (! $user) {
            return $this->error('Unauthenticated.', 401);
        }

        $isMemberRole = $user->hasRole('member') || $user->role_type === 'member';

        if (! $isMemberRole) {
            return $this->error('Only members can access this endpoint.', 403);
        }

        return null;
    }
}
