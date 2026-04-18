<?php

namespace App\Http\Controllers;

use App\Enum\UserStatus;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserLookupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->can('user.view') && ! $user->can('committee.member.assignment.create') && ! $user->can('committee.member.assignment.update')) {
            return $this->error('You are not authorized to view admin users.', 403);
        }

        $search = trim((string) $request->string('search', ''));
        $perPage = min(max((int) $request->integer('per_page', 10), 5), 25);

        $query = User::query()
            ->with('roles:id,name')
            ->where('status', UserStatus::Active->value)
            ->where(function ($builder) {
                $builder->whereHas('roles')
                    ->orWhereNotNull('role_type');
            });

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query
            ->orderBy('name')
            ->limit($perPage)
            ->get()
            ->map(fn (User $lookupUser) => [
                'id' => $lookupUser->id,
                'name' => $lookupUser->name,
                'email' => $lookupUser->email,
                'phone' => $lookupUser->phone,
                'username' => $lookupUser->username,
                'role_type' => $lookupUser->role_type,
                'roles' => $lookupUser->roles->pluck('name')->values(),
            ])
            ->values();

        return $this->success($users, 'Admin users retrieved successfully.');
    }
}