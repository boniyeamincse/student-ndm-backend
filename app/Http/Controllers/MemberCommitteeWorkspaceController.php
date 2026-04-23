<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresMemberAccess;
use App\Models\CommitteeApplication;
use App\Models\CommitteeMemberAssignment;
use App\Models\MemberActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberCommitteeWorkspaceController extends Controller
{
    use EnsuresMemberAccess;

    public function myCommittee(): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $memberId = auth()->user()->member?->id;

        if (! $memberId) {
            return $this->success(null, 'No linked member profile found.');
        }

        $assignment = CommitteeMemberAssignment::query()
            ->with(['committee:id,name,slug,status', 'position:id,name,name_bn'])
            ->where('member_id', $memberId)
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->orderByDesc('created_at')
            ->first();

        return $this->success($assignment, $assignment ? 'My committee retrieved successfully.' : 'No active committee assignment found.');
    }

    public function members(Request $request): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $memberId = $request->user()->member?->id;
        if (! $memberId) {
            return $this->success([], 'No linked member profile found.');
        }

        $primary = CommitteeMemberAssignment::query()
            ->where('member_id', $memberId)
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->first();

        if (! $primary?->committee_id) {
            return $this->success([], 'No committee members found.');
        }

        $rows = CommitteeMemberAssignment::query()
            ->with(['member:id,member_no,full_name,photo,status', 'position:id,name,name_bn'])
            ->where('committee_id', $primary->committee_id)
            ->where('is_active', true)
            ->orderByDesc('is_leadership')
            ->orderByDesc('is_primary')
            ->limit(200)
            ->get();

        return $this->success($rows, 'Committee members retrieved successfully.');
    }

    public function activities(Request $request): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $memberId = $request->user()->member?->id;
        if (! $memberId) {
            return $this->success([], 'No linked member profile found.');
        }

        $primary = CommitteeMemberAssignment::query()
            ->where('member_id', $memberId)
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->first();

        if (! $primary?->committee_id) {
            return $this->success([], 'No committee activities found.');
        }

        $rows = MemberActivity::query()
            ->where('committee_id', $primary->committee_id)
            ->orderByDesc('activity_at')
            ->limit(50)
            ->get();

        return $this->success($rows, 'Committee activities retrieved successfully.');
    }

    public function apply(Request $request): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $payload = $request->validate([
            'desired_committee_level' => ['nullable', 'string', 'max:100'],
            'desired_committee_id' => ['nullable', 'integer', 'exists:committees,id'],
            'note' => ['nullable', 'string', 'max:3000'],
        ]);

        $item = CommitteeApplication::query()->create([
            'user_id' => $request->user()->id,
            'desired_committee_level' => $payload['desired_committee_level'] ?? null,
            'desired_committee_id' => $payload['desired_committee_id'] ?? null,
            'note' => $payload['note'] ?? null,
            'status' => 'pending',
        ]);

        return $this->success($item, 'Committee application submitted successfully.', 201);
    }
}
