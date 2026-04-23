<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresMemberAccess;
use App\Models\Announcement;
use App\Models\Discussion;
use App\Models\MemberMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberCommunicationController extends Controller
{
    use EnsuresMemberAccess;

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
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $item = MemberMessage::query()->create([
            'sender_id' => $request->user()->id,
            'recipient_id' => $payload['recipient_id'],
            'subject' => $payload['subject'] ?? null,
            'body' => $payload['body'],
            'is_read' => false,
        ]);

        return $this->success($item, 'Message sent successfully.', 201);
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
