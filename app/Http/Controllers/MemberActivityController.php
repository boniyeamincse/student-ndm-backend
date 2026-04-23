<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresMemberAccess;
use App\Models\MemberActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberActivityController extends Controller
{
    use EnsuresMemberAccess;

    public function index(Request $request): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'type' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $request->user();
        $perPage = (int) ($request->input('per_page', 20));

        $query = MemberActivity::query()
            ->where('user_id', $user->id)
            ->orderByDesc('activity_at')
            ->orderByDesc('id');

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        $paginator = $query->paginate($perPage);

        return $this->success($paginator->items(), 'Member activities retrieved successfully.', 200, [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }
}
