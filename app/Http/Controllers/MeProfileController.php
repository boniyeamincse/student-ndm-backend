<?php

namespace App\Http\Controllers;

use App\Http\Requests\MyCommitteeAssignmentsListRequest;
use App\Http\Requests\MyLeaderRequest;
use App\Http\Requests\MySubordinatesListRequest;
use App\Http\Requests\UpdateAccountSettingsRequest;
use App\Http\Requests\UpdateMyProfilePhotoRequest;
use App\Http\Requests\UpdateMyProfileRequest;
use App\Http\Resources\MyAccountSettingsResource;
use App\Http\Resources\MyCommitteeAssignmentResource;
use App\Http\Resources\MyLeaderResource;
use App\Http\Resources\MyMemberOverviewResource;
use App\Http\Resources\MyProfileResource;
use App\Http\Resources\MySubordinateResource;
use App\Http\Resources\ProfileSummaryResource;
use App\Services\AccountSettingService;
use App\Services\SelfProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class MeProfileController extends Controller
{
    public function __construct(
        private readonly SelfProfileService $selfProfileService,
        private readonly AccountSettingService $accountSettingService,
    ) {
    }

    public function profile(): JsonResponse
    {
        if (! auth()->user()->can('self.profile.view')) {
            return $this->error('You are not authorized to view your profile.', 403);
        }

        $data = $this->selfProfileService->getMyProfile(auth()->user());

        return $this->success(new MyProfileResource($data), 'My profile retrieved successfully.');
    }

    public function updateProfile(UpdateMyProfileRequest $request): JsonResponse
    {
        if (! auth()->user()->can('self.profile.update')) {
            return $this->error('You are not authorized to update your profile.', 403);
        }

        try {
            $data = $this->selfProfileService->updateMyProfile($request->user(), $request->validated());
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new MyProfileResource($data), 'My profile updated successfully.');
    }

    public function updatePhoto(UpdateMyProfilePhotoRequest $request): JsonResponse
    {
        if (! auth()->user()->can('self.profile.photo.update')) {
            return $this->error('You are not authorized to update profile photo.', 403);
        }

        $result = $this->selfProfileService->updatePhoto($request->user(), $request->file('photo'));

        return $this->success($result, 'Profile photo updated successfully.');
    }

    public function accountSettings(): JsonResponse
    {
        $settings = $this->accountSettingService->getOrCreate(auth()->user());

        return $this->success(new MyAccountSettingsResource($settings), 'Account settings retrieved successfully.');
    }

    public function updateAccountSettings(UpdateAccountSettingsRequest $request): JsonResponse
    {
        if (! auth()->user()->can('self.account.settings.update')) {
            return $this->error('You are not authorized to update account settings.', 403);
        }

        $settings = $this->accountSettingService->update($request->user(), $request->validated());

        return $this->success(new MyAccountSettingsResource($settings), 'Account settings updated successfully.');
    }

    public function memberOverview(): JsonResponse
    {
        if (! auth()->user()->can('self.profile.view')) {
            return $this->error('You are not authorized to view member overview.', 403);
        }

        $member = $this->selfProfileService->memberOverview(auth()->user());

        return $this->success(
            $member ? new MyMemberOverviewResource($member) : null,
            $member ? 'Member overview retrieved successfully.' : 'No linked member profile found.'
        );
    }

    public function committeeAssignments(MyCommitteeAssignmentsListRequest $request): JsonResponse
    {
        if (! auth()->user()->can('self.profile.view')) {
            return $this->error('You are not authorized to view committee assignments.', 403);
        }

        $paginator = $this->selfProfileService->committeeAssignments($request->user(), $request->validated());

        return $this->success(
            MyCommitteeAssignmentResource::collection($paginator),
            'My committee assignments retrieved successfully.',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }

    public function leader(MyLeaderRequest $request): JsonResponse
    {
        if (! auth()->user()->can('self.profile.view')) {
            return $this->error('You are not authorized to view leader details.', 403);
        }

        $leader = $this->selfProfileService->leader($request->user(), $request->validated('assignment_id'));

        return $this->success(
            $leader ? new MyLeaderResource($leader) : null,
            $leader ? 'Leader retrieved successfully.' : 'No active leader found.'
        );
    }

    public function subordinates(MySubordinatesListRequest $request): JsonResponse
    {
        if (! auth()->user()->can('self.profile.view')) {
            return $this->error('You are not authorized to view subordinates.', 403);
        }

        $paginator = $this->selfProfileService->subordinates($request->user(), $request->validated());

        return $this->success(
            MySubordinateResource::collection($paginator),
            'My subordinates retrieved successfully.',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }

    public function profileSummary(): JsonResponse
    {
        if (! auth()->user()->can('self.profile.view')) {
            return $this->error('You are not authorized to view profile summary.', 403);
        }

        $summary = $this->selfProfileService->summary(auth()->user());

        return $this->success(new ProfileSummaryResource($summary), 'Profile summary retrieved successfully.');
    }
}
