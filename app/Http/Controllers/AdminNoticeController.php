<?php

namespace App\Http\Controllers;

use App\Enum\NoticeStatus;
use App\Http\Requests\NoticeListRequest;
use App\Http\Requests\StoreNoticeAttachmentRequest;
use App\Http\Requests\StoreNoticeRequest;
use App\Http\Requests\UpdateNoticePinRequest;
use App\Http\Requests\UpdateNoticeRequest;
use App\Http\Requests\UpdateNoticeStatusRequest;
use App\Http\Resources\NoticeDetailResource;
use App\Http\Resources\NoticeListResource;
use App\Http\Resources\NoticesSummaryResource;
use App\Models\Notice;
use App\Services\NoticeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminNoticeController extends Controller
{
    public function __construct(private readonly NoticeService $service)
    {
    }

    public function index(NoticeListRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Notice::class);

        $paginator = $this->service->list($request->validated());

        return $this->success(
            NoticeListResource::collection($paginator),
            'Notices retrieved successfully.',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }

    public function store(StoreNoticeRequest $request): JsonResponse
    {
        $this->authorize('create', Notice::class);

        try {
            $notice = $this->service->create($request->validated(), $request->user()->id);
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new NoticeDetailResource($notice), 'Notice created successfully.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $model = Notice::findOrFail($id);
        $this->authorize('view', $model);

        $notice = $this->service->detail($id);

        return $this->success(new NoticeDetailResource($notice), 'Notice detail retrieved successfully.');
    }

    public function update(UpdateNoticeRequest $request, int $id): JsonResponse
    {
        $model = Notice::findOrFail($id);
        $this->authorize('update', $model);

        try {
            $notice = $this->service->update($model, $request->validated(), $request->user()->id);
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new NoticeDetailResource($notice), 'Notice updated successfully.');
    }

    public function updateStatus(UpdateNoticeStatusRequest $request, int $id): JsonResponse
    {
        $model = Notice::findOrFail($id);
        $this->authorize('update', $model);

        try {
            $notice = $this->service->changeStatus(
                $model,
                NoticeStatus::from($request->validated('status')),
                $request->input('note'),
                $request->user()->id
            );
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new NoticeDetailResource($notice), 'Notice status updated successfully.');
    }

    public function updatePin(UpdateNoticePinRequest $request, int $id): JsonResponse
    {
        $model = Notice::findOrFail($id);
        $this->authorize('pin', $model);

        try {
            $notice = $this->service->updatePin($model, $request->boolean('is_pinned'), $request->user()->id);
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new NoticeDetailResource($notice), 'Notice pin status updated successfully.');
    }

    public function addAttachments(StoreNoticeAttachmentRequest $request, int $id): JsonResponse
    {
        $model = Notice::findOrFail($id);
        $this->authorize('manageAttachments', $model);

        try {
            $notice = $this->service->addAttachments($model, $request->file('attachments', []), $request->user()->id);
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new NoticeDetailResource($notice), 'Notice attachments added successfully.');
    }

    public function removeAttachment(int $id, int $attachmentId): JsonResponse
    {
        $model = Notice::findOrFail($id);
        $this->authorize('manageAttachments', $model);

        try {
            $notice = $this->service->deleteAttachment($model, $attachmentId);
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new NoticeDetailResource($notice), 'Notice attachment removed successfully.');
    }

    public function summary(Request $request): JsonResponse
    {
        if (! $request->user()->can('notice.summary.view')) {
            return $this->error('You are not authorized to view notice summary.', 403);
        }

        $summary = $this->service->summary($request->boolean('include_committee_summary'));

        return $this->success(new NoticesSummaryResource($summary), 'Notice summary retrieved successfully.');
    }

    public function publish(Request $request, int $id): JsonResponse
    {
        $model = Notice::findOrFail($id);
        $this->authorize('publish', $model);

        try {
            $notice = $this->service->publish($model, $request->user()->id, $request->input('note'));
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new NoticeDetailResource($notice), 'Notice published successfully.');
    }

    public function unpublish(Request $request, int $id): JsonResponse
    {
        $model = Notice::findOrFail($id);
        $this->authorize('unpublish', $model);

        try {
            $notice = $this->service->unpublish($model, $request->user()->id, $request->input('note'));
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new NoticeDetailResource($notice), 'Notice unpublished successfully.');
    }

    public function archive(Request $request, int $id): JsonResponse
    {
        $model = Notice::findOrFail($id);
        $this->authorize('archive', $model);

        try {
            $notice = $this->service->archive($model, $request->user()->id, $request->input('note'));
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new NoticeDetailResource($notice), 'Notice archived successfully.');
    }

    public function destroy(int $id): JsonResponse
    {
        $model = Notice::findOrFail($id);
        $this->authorize('delete', $model);

        try {
            $this->service->delete($model, auth()->id());
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(null, 'Notice deleted successfully.');
    }

    public function restore(int $id): JsonResponse
    {
        $model = Notice::withTrashed()->findOrFail($id);
        $this->authorize('restore', $model);

        try {
            $notice = $this->service->restore($model, auth()->id());
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new NoticeDetailResource($notice), 'Notice restored successfully.');
    }
}
