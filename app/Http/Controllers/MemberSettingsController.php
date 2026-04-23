<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresMemberAccess;
use App\Http\Requests\Api\V1\ChangePasswordRequest;
use App\Http\Requests\UpdateAccountSettingsRequest;
use App\Http\Resources\MyAccountSettingsResource;
use App\Services\AccountSettingService;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class MemberSettingsController extends Controller
{
    use EnsuresMemberAccess;

    public function __construct(
        private readonly AccountSettingService $accountSettingService,
        private readonly AuthService $authService,
    ) {
    }

    public function show(): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $settings = $this->accountSettingService->getOrCreate(auth()->user());
        $resource = new MyAccountSettingsResource($settings);
        $data = $resource->toArray(request());

        return $this->success([
            'account' => [
                'language' => $data['language'],
                'timezone' => $data['timezone'],
            ],
            'privacy' => [
                'profile_visibility' => $data['profile_visibility'],
                'show_email' => $data['show_email'],
                'show_phone' => $data['show_phone'],
                'show_address' => $data['show_address'],
            ],
            'notifications' => [
                'notification_email_enabled' => $data['notification_email_enabled'],
                'notification_sms_enabled' => $data['notification_sms_enabled'],
                'notification_push_enabled' => $data['notification_push_enabled'],
            ],
            'updated_at' => $data['updated_at'],
        ], 'Member settings retrieved successfully.');
    }

    public function updateAccount(UpdateAccountSettingsRequest $request): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $payload = array_intersect_key($request->validated(), array_flip([
            'language',
            'timezone',
        ]));

        $settings = $this->accountSettingService->update($request->user(), $payload);

        return $this->success(new MyAccountSettingsResource($settings), 'Account settings updated successfully.');
    }

    public function updatePrivacy(UpdateAccountSettingsRequest $request): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $payload = array_intersect_key($request->validated(), array_flip([
            'profile_visibility',
            'show_email',
            'show_phone',
            'show_address',
        ]));

        $settings = $this->accountSettingService->update($request->user(), $payload);

        return $this->success(new MyAccountSettingsResource($settings), 'Privacy settings updated successfully.');
    }

    public function updateNotifications(UpdateAccountSettingsRequest $request): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $payload = array_intersect_key($request->validated(), array_flip([
            'notification_email_enabled',
            'notification_sms_enabled',
            'notification_push_enabled',
        ]));

        $settings = $this->accountSettingService->update($request->user(), $payload);

        return $this->success(new MyAccountSettingsResource($settings), 'Notification settings updated successfully.');
    }

    public function updatePassword(ChangePasswordRequest $request): JsonResponse
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $this->authService->changePassword($request->user(), $request);

        return $this->success(null, 'Password changed successfully. Please sign in again.');
    }
}