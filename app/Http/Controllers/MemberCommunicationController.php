<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresMemberAccess;
use App\Models\Announcement;
use App\Models\Committee;
use App\Models\Discussion;
use App\Models\MemberMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberCommunicationController extends Controller
{
    use EnsuresMemberAccess;

    public function messageTargets(): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $committees = Committee::query()
            ->select(['id', 'name'])
            ->whereHas('committeeAssignments', function ($q) {
                $q->where('is_active', true);
            })
            ->orderBy('name')
            ->get();

        return $this->success([
            'recipient_modes' => [
                ['value' => 'all', 'label' => 'All members'],
                ['value' => 'active', 'label' => 'Only active members'],
                ['value' => 'committee', 'label' => 'By committee'],
            ],
            'committees' => $committees,
        ], 'Message targets retrieved successfully.');
    }

    public function messages(Request $request): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $rows = MemberMessage::query()
            ->with('sender:id,name,email', 'recipient:id,name,email')
            ->where(function ($q) use ($request) {
                $q->where('sender_id', $request->user()->id)
                    ->orWhere('recipient_id', $request->user()->id);
            })
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return $this->success($rows, 'Messages retrieved successfully.');
    }

    public function messageDetail(Request $request, int $id): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $item = MemberMessage::query()
            ->with('sender:id,name,email', 'recipient:id,name,email')
            ->where('id', $id)
            ->where(function ($q) use ($request) {
                $q->where('sender_id', $request->user()->id)
                    ->orWhere('recipient_id', $request->user()->id);
            })
            ->firstOrFail();

        if ((int) $item->recipient_id === (int) $request->user()->id && ! $item->is_read) {
            $item->update(['is_read' => true, 'read_at' => now()]);
            $item->refresh();
        }

        return $this->success($item, 'Message detail retrieved successfully.');
    }

    public function sendMessage(Request $request): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $payload = $request->validate([
            'recipient_mode' => ['nullable', 'string', 'in:single,all,active,committee'],
            'recipient_id' => ['nullable', 'integer', 'exists:users,id', 'required_without_all:send_to_all,recipient_mode'],
            'send_to_all' => ['nullable', 'boolean'],
            'committee_id' => ['nullable', 'integer', 'exists:committees,id', 'required_if:recipient_mode,committee'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $sendToAll = (bool) ($payload['send_to_all'] ?? false);
        $recipientMode = $sendToAll ? 'all' : ($payload['recipient_mode'] ?? 'single');
        $senderId = (int) $request->user()->id;
        $subject = $payload['subject'] ?? null;
        $body = $payload['body'];

        if ($recipientMode === 'single') {
            $recipientId = (int) ($payload['recipient_id'] ?? 0);
            if ($recipientId === $senderId) {
                return $this->error('You cannot send a message to yourself.', 422);
            }

            $item = MemberMessage::query()->create([
                'sender_id' => $senderId,
                'recipient_id' => $recipientId,
                'subject' => $subject,
                'body' => $body,
                'is_read' => false,
            ]);

            return $this->success($item, 'Message sent successfully.', 201);
        }

        $recipientQuery = User::query()->where('id', '!=', $senderId);

        if ($recipientMode === 'all') {
            $recipientQuery->whereHas('member');
        } elseif ($recipientMode === 'active') {
            $recipientQuery->whereHas('member', function ($q) {
                $q->where('status', 'active');
            });
        } elseif ($recipientMode === 'committee') {
            $committeeId = (int) ($payload['committee_id'] ?? 0);
            $recipientQuery->whereHas('member.committeeAssignments', function ($q) use ($committeeId) {
                $q->where('committee_id', $committeeId)
                    ->where('is_active', true);
            });
        }

        $recipientIds = $recipientQuery->pluck('id')->unique()->values();

        if ($recipientIds->isEmpty()) {
            return $this->error('No member recipients found for the selected target.', 422);
        }

        $now = now();
        $rows = $recipientIds->map(function (int $recipientId) use ($senderId, $subject, $body, $now) {
            return [
                'sender_id' => $senderId,
                'recipient_id' => $recipientId,
                'subject' => $subject,
                'body' => $body,
                'is_read' => false,
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            MemberMessage::query()->insert($chunk);
        }

        return $this->success([
            'scope' => $recipientMode,
            'sent_count' => count($rows),
            'committee_id' => $recipientMode === 'committee' ? (int) $payload['committee_id'] : null,
        ], 'Message broadcast sent successfully.', 201);
    }

    public function announcements(): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $rows = Announcement::query()
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('publish_at')->orWhere('publish_at', '<=', now());
            })
            ->orderByDesc('publish_at')
            ->limit(100)
            ->get();

        return $this->success($rows, 'Announcements retrieved successfully.');
    }

    public function discussions(): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $rows = Discussion::query()
            ->with('user:id,name,email')
            ->where('status', 'open')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return $this->success($rows, 'Discussions retrieved successfully.');
    }

    public function createDiscussion(Request $request): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $payload = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $item = Discussion::query()->create([
            'user_id' => $request->user()->id,
            'title' => $payload['title'],
            'body' => $payload['body'],
            'status' => 'open',
        ]);

        return $this->success($item, 'Discussion created successfully.', 201);
    }
}
