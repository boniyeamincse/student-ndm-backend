<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
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
        return $user->can('post.view');
    }

    public function view(User $user, Post $post): bool
    {
        return $user->can('post.view.detail');
    }

    public function create(User $user): bool
    {
        return $user->can('post.create');
    }

    public function update(User $user, Post $post): bool
    {
        return $user->can('post.update');
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->can('post.delete');
    }

    public function restore(User $user, Post $post): bool
    {
        return $user->can('post.restore');
    }

    public function publish(User $user, Post $post): bool
    {
        return $user->can('post.publish');
    }

    public function unpublish(User $user, Post $post): bool
    {
        return $user->can('post.unpublish');
    }

    public function archive(User $user, Post $post): bool
    {
        return $user->can('post.archive');
    }

    public function feature(User $user, Post $post): bool
    {
        return $user->can('post.feature');
    }
}
