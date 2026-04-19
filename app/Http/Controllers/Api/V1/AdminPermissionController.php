<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use App\Services\PermissionService;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\JsonResponse;

class AdminPermissionController extends Controller
{
    public function __construct(private readonly PermissionService $permissionService) {}

    /**
     * GET /api/v1/admin/permissions
     * List all permissions.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        $permissions = $this->permissionService->list();

        return $this->success(
            PermissionResource::collection($permissions),
            'Permissions retrieved successfully.'
        );
    }

    /**
     * GET /api/v1/admin/permissions/grouped
     * Get permissions grouped by module for role assignment UI.
     */
    public function grouped(): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        $grouped = $this->permissionService->getGrouped();

        return $this->success(
            $grouped,
            'Grouped permissions retrieved successfully.'
        );
    }

    /**
     * GET /api/v1/admin/permissions/{id}
     * Get single permission details.
     */
    public function show(Permission $permission): JsonResponse
    {
        $this->authorize('view', $permission);

        return $this->success(
            new PermissionResource($permission),
            'Permission retrieved successfully.'
        );
    }
}
