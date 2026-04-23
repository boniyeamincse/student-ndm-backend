<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresMemberAccess;
use App\Models\MemberNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberNotificationController extends Controller
{
    use EnsuresMemberAccess;

    public function index(Request $request): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'unread_only' => ['nullable', 'boolean'],
        ]);

        $perPage = (int) ($request->input('per_page', 20));

        $query = MemberNotification::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at');

        if ($request->boolean('unread_only')) {
            $query->where('is_read', false);
        }

        $paginator = $query->paginate($perPage);

        return $this->success($paginator->items(), 'Member notifications retrieved successfully.', 200, [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'unread_count' => MemberNotification::query()->where('user_id', $request->user()->id)->where('is_read', false)->count(),
        ]);
    }

    public function markAsRead(int $id): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $item = MemberNotification::query()
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        if (! $item->is_read) {
            $item->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return $this->success($item->fresh(), 'Notification marked as read.');
    }

    public function markAllAsRead(): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        MemberNotification::query()
            ->where('user_id', auth()->id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return $this->success(null, 'All notifications marked as read.');
    }
}
