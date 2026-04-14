<?php

namespace App\Policies;

use App\Models\PostCategory;
use App\Models\User;

class PostCategoryPolicy
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
        return $user->can('post.category.view');
    }

    public function view(User $user, PostCategory $category): bool
    {
        return $user->can('post.category.view');
    }

    public function create(User $user): bool
    {
        return $user->can('post.category.create');
    }

    public function update(User $user, PostCategory $category): bool
    {
        return $user->can('post.category.update');
    }

    public function delete(User $user, PostCategory $category): bool
    {
        return $user->can('post.category.delete');
    }

    public function restore(User $user, PostCategory $category): bool
    {
        return $user->can('post.category.restore');
    }
}
