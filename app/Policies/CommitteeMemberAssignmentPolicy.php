<?php

namespace App\Policies;

use App\Models\CommitteeMemberAssignment;
use App\Models\User;

class CommitteeMemberAssignmentPolicy
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
        return $user->can('committee.member.assignment.view');
    }

    public function view(User $user, CommitteeMemberAssignment $assignment): bool
    {
        return $user->can('committee.member.assignment.view.detail');
    }

    public function create(User $user): bool
    {
        return $user->can('committee.member.assignment.create');
    }

    public function update(User $user, CommitteeMemberAssignment $assignment): bool
    {
        return $user->can('committee.member.assignment.update');
    }

    public function updateStatus(User $user, CommitteeMemberAssignment $assignment): bool
    {
        return $user->can('committee.member.assignment.status.update');
    }

    public function transfer(User $user, CommitteeMemberAssignment $assignment): bool
    {
        return $user->can('committee.member.assignment.transfer');
    }

    public function delete(User $user, CommitteeMemberAssignment $assignment): bool
    {
        return $user->can('committee.member.assignment.delete');
    }

    public function restore(User $user, CommitteeMemberAssignment $assignment): bool
    {
        return $user->can('committee.member.assignment.restore');
    }
}
