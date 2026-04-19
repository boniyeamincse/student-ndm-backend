<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncUserRolesRequest;
use App\Http\Resources\UserBasicResource;
use App\Models\User;
use App\Services\UserRoleService;
use Illuminate\Http\JsonResponse;

class AdminUserRoleController extends Controller
{
    public function __construct(private readonly UserRoleService $userRoleService) {}

    /**
     * PATCH /api/v1/admin/users/{user}/roles
     * Sync roles for a specific user.
     */
    public function syncRoles(SyncUserRolesRequest $request, User $user): JsonResponse
    {
        abort_unless(auth()->user()?->can('role.assign'), 403, 'You are not authorized to assign roles.');

        $updated = $this->userRoleService->syncUserRoles($user, $request->validated()['roles'], auth()->user());

        return $this->success([
            'user' => new UserBasicResource($updated),
            'roles' => $updated->roles->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
            ])->values(),
        ], 'User roles updated successfully.');
    }
}
