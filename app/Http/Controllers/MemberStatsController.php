<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresMemberAccess;
use App\Models\MemberActivity;
use App\Models\MemberBadge;
use App\Models\MemberNotification;
use App\Models\MemberPoint;
use Illuminate\Http\JsonResponse;

class MemberStatsController extends Controller
{
    use EnsuresMemberAccess;

    public function index(): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $user = auth()->user();

        $points = MemberPoint::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['member_id' => $user->member?->id, 'total_points' => 0, 'current_rank' => 'rookie']
        );

        $badges = MemberBadge::query()
            ->where('user_id', $user->id)
            ->orderByDesc('awarded_at')
            ->limit(12)
            ->get(['id', 'name', 'icon', 'awarded_at']);

        $activityCount = MemberActivity::query()->where('user_id', $user->id)->count();
        $notificationsCount = MemberNotification::query()->where('user_id', $user->id)->where('is_read', false)->count();

        return $this->success([
            'total_points' => (int) $points->total_points,
            'current_rank' => (string) $points->current_rank,
            'badges' => $badges,
            'activity_count' => $activityCount,
            'notifications_count' => $notificationsCount,
        ], 'Member stats retrieved successfully.');
    }
}
