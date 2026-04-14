<?php

namespace App\Http\Controllers;

use App\Services\AdminMenuService;
use Illuminate\Http\JsonResponse;

class AdminMenuController extends Controller
{
    public function __construct(private readonly AdminMenuService $service)
    {
    }

    /**
     * GET /api/v1/admin/menu
     * Return role-aware admin sidebar menu structure.
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();

        if (! $user->hasAnyRole(['superadmin', 'admin'])) {
            return $this->error('You are not authorized to view the admin menu.', 403);
        }

        return $this->success(
            $this->service->forUser($user),
            'Admin menu retrieved successfully.'
        );
    }
}
