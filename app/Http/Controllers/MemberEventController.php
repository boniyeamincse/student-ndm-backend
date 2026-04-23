<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresMemberAccess;
use App\Models\EventProgram;
use App\Models\EventRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberEventController extends Controller
{
    use EnsuresMemberAccess;

    public function upcoming(Request $request): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $rows = EventProgram::query()
            ->where('status', 'upcoming')
            ->where(function ($q) {
                $q->whereNull('event_at')->orWhere('event_at', '>=', now());
            })
            ->orderBy('event_at')
            ->limit(50)
            ->get();

        return $this->success($rows, 'Upcoming events retrieved successfully.');
    }

    public function registered(Request $request): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $rows = EventRegistration::query()
            ->with('event:id,title,slug,event_at,status,location')
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'approved'])
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return $this->success($rows, 'Registered events retrieved successfully.');
    }

    public function history(Request $request): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $rows = EventRegistration::query()
            ->with('event:id,title,slug,event_at,status,location')
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['completed', 'cancelled', 'rejected'])
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return $this->success($rows, 'Event history retrieved successfully.');
    }

    public function join(Request $request, int $id): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $event = EventProgram::query()->findOrFail($id);

        if ($event->status !== 'upcoming') {
            return $this->error('This event is not open for registration.', 422);
        }

        $existing = EventRegistration::query()
            ->where('event_program_id', $event->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existing && in_array($existing->status, ['pending', 'approved'], true)) {
            return $this->error('You have already joined this event.', 422);
        }

        $item = EventRegistration::query()->updateOrCreate(
            [
                'event_program_id' => $event->id,
                'user_id' => $request->user()->id,
            ],
            [
                'status' => 'pending',
                'applied_at' => now(),
            ]
        );

        return $this->success($item, 'Event join request submitted successfully.', 201);
    }
}
