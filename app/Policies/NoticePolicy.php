<?php

namespace App\Policies;

use App\Models\Notice;
use App\Models\User;

class NoticePolicy
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
        return $user->can('notice.view');
    }

    public function view(User $user, Notice $notice): bool
    {
        return $user->can('notice.view.detail');
    }

    public function create(User $user): bool
    {
        return $user->can('notice.create');
    }

    public function update(User $user, Notice $notice): bool
    {
        return $user->can('notice.update');
    }

    public function delete(User $user, Notice $notice): bool
    {
        return $user->can('notice.delete');
    }

    public function restore(User $user, Notice $notice): bool
    {
        return $user->can('notice.restore');
    }

    public function publish(User $user, Notice $notice): bool
    {
        return $user->can('notice.publish');
    }

    public function unpublish(User $user, Notice $notice): bool
    {
        return $user->can('notice.unpublish');
    }

    public function archive(User $user, Notice $notice): bool
    {
        return $user->can('notice.archive');
    }

    public function pin(User $user, Notice $notice): bool
    {
        return $user->can('notice.pin');
    }

    public function manageAttachments(User $user, Notice $notice): bool
    {
        return $user->can('notice.attachment.manage');
    }
}
