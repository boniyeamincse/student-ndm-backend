<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleListRequest;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Requests\SyncRolePermissionsRequest;
use App\Http\Resources\RoleDetailResource;
use App\Http\Resources\RoleListResource;
use App\Services\RoleService;
use Spatie\Permission\Models\Role;
use Illuminate\Http\JsonResponse;

class AdminRoleController extends Controller
{
    public function __construct(private readonly RoleService $roleService) {}

    /**
     * GET /api/v1/admin/roles
     * List all roles with pagination, filters, and search.
     */
    public function index(RoleListRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $paginator = $this->roleService->list($request);

        return $this->success(
            RoleListResource::collection($paginator),
            'Roles retrieved successfully.',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ]
        );
    }

    /**
     * POST /api/v1/admin/roles
     * Create a new role.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $this->authorize('create', Role::class);

        $role = $this->roleService->create($request->validated());

        return $this->success(
            new RoleDetailResource($role),
            'Role created successfully.',
            201
        );
    }

    /**
     * GET /api/v1/admin/roles/{id}
     * Get full role details with permissions and users.
     */
    public function show(Role $role): JsonResponse
    {
        $this->authorize('view', $role);

        $roleWithDetails = $this->roleService->detail($role->id);

        return $this->success(
            new RoleDetailResource($roleWithDetails),
            'Role retrieved successfully.'
        );
    }

    /**
     * PUT /api/v1/admin/roles/{id}
     * Update role basic details.
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $this->authorize('update', $role);

        $updatedRole = $this->roleService->update($role->id, $request->validated());

        return $this->success(
            new RoleDetailResource($updatedRole),
            'Role updated successfully.'
        );
    }

    /**
     * PATCH /api/v1/admin/roles/{id}/permissions
     * Sync permissions for a role.
     */
    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role): JsonResponse
    {
        $this->authorize('update', $role);

        $role = $this->roleService->syncPermissions($role->id, $request->validated()['permissions']);

        return $this->success(
            new RoleDetailResource($role),
            'Role permissions updated successfully.'
        );
    }

    /**
     * DELETE /api/v1/admin/roles/{id}
     * Delete a role.
     */
    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('delete', $role);

        $this->roleService->delete($role->id);

        return $this->success(
            null,
            'Role deleted successfully.',
            204
        );
    }

    /**
     * GET /api/v1/admin/roles-summary
     * Get summary counts for roles.
     */
    public function summary(): JsonResponse
    {
        return $this->success(
            $this->roleService->getSummary(),
            'Role summary retrieved successfully.'
        );
    }
}
