<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresMemberAccess;
use App\Models\CertificateRequest;
use App\Models\CommitteeApplication;
use App\Models\EventRegistration;
use App\Models\MembershipApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberApplicationCenterController extends Controller
{
    use EnsuresMemberAccess;

    public function index(Request $request): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $user = $request->user();

        $membership = MembershipApplication::query()
            ->where('email', $user->email)
            ->latest('id')
            ->first();

        $committee = CommitteeApplication::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(20)
            ->get();

        $event = EventRegistration::query()
            ->with('event:id,title,event_at,status')
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(20)
            ->get();

        $certificate = CertificateRequest::query()
            ->with('event:id,title,event_at,status')
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(20)
            ->get();

        return $this->success([
            'membership' => $membership,
            'committee_applications' => $committee,
            'event_applications' => $event,
            'certificate_requests' => $certificate,
        ], 'Member applications retrieved successfully.');
    }

    public function membershipStatus(Request $request): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $item = MembershipApplication::query()
            ->where('email', $request->user()->email)
            ->latest('id')
            ->first();

        return $this->success($item, $item ? 'Membership application status retrieved successfully.' : 'No membership application found.');
    }

    public function eventApply(Request $request): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $payload = $request->validate([
            'event_id' => ['required', 'integer', 'exists:event_programs,id'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $item = EventRegistration::query()->updateOrCreate(
            [
                'event_program_id' => $payload['event_id'],
                'user_id' => $request->user()->id,
            ],
            [
                'status' => 'pending',
                'applied_at' => now(),
                'meta' => ['note' => $payload['note'] ?? null],
            ]
        );

        return $this->success($item, 'Event application submitted successfully.', 201);
    }

    public function certificateRequest(Request $request): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $payload = $request->validate([
            'purpose' => ['required', 'string', 'max:2000'],
            'event_id' => ['nullable', 'integer', 'exists:event_programs,id'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $item = CertificateRequest::query()->create([
            'user_id' => $request->user()->id,
            'event_program_id' => $payload['event_id'] ?? null,
            'purpose' => $payload['purpose'],
            'note' => $payload['note'] ?? null,
            'status' => 'pending',
        ]);

        return $this->success($item, 'Certificate request submitted successfully.', 201);
    }
}
