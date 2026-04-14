<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreMembershipApplicationRequest;
use App\Http\Resources\Api\V1\MembershipApplicationResource;
use App\Services\MembershipApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MembershipApplicationController extends Controller
{
    public function __construct(private readonly MembershipApplicationService $service)
    {
    }

    /**
     * POST /api/v1/membership/apply
     * Public endpoint — no authentication required.
     */
    public function apply(StoreMembershipApplicationRequest $request): JsonResponse
    {
        $application = $this->service->submit($request->validated(), $request);

        return $this->success(
            data: [
                'application_no' => $application->application_no,
                'status'         => $application->status->value,
            ],
            message: 'Membership application submitted successfully. We will review your application and notify you.',
            status: 201,
        );
    }
}
