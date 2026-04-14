<?php

namespace App\Services;

use App\Enum\NoticeAttachmentFileType;
use App\Models\Notice;
use App\Models\NoticeAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class NoticeAttachmentService
{
    public function addAttachments(Notice $notice, array $files, int $actorId): Notice
    {
        return DB::transaction(function () use ($notice, $files, $actorId) {
            $startOrder = (int) NoticeAttachment::query()->where('notice_id', $notice->id)->max('sort_order');
            $order = $startOrder > 0 ? $startOrder + 1 : 1;

            foreach ($files as $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $path = $file->store('notices/attachments', 'public');
                $extension = strtolower($file->getClientOriginalExtension());

                NoticeAttachment::create([
                    'notice_id' => $notice->id,
                    'file_name' => basename($path),
                    'original_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_extension' => $extension,
                    'mime_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'file_type' => $this->mapFileType($extension),
                    'sort_order' => $order,
                    'uploaded_by' => $actorId,
                ]);

                $order++;
            }

            $notice->update([
                'attachment_count' => NoticeAttachment::query()->where('notice_id', $notice->id)->count(),
            ]);

            return $notice->fresh(['attachments.uploader:id,name,email']);
        });
    }

    public function deleteAttachment(Notice $notice, int $attachmentId): Notice
    {
        return DB::transaction(function () use ($notice, $attachmentId) {
            $attachment = NoticeAttachment::query()->findOrFail($attachmentId);

            if ((int) $attachment->notice_id !== (int) $notice->id) {
                throw ValidationException::withMessages([
                    'attachment' => ['Attachment does not belong to this notice.'],
                ]);
            }

            if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }

            $attachment->delete();

            $notice->update([
                'attachment_count' => NoticeAttachment::query()->where('notice_id', $notice->id)->count(),
            ]);

            return $notice->fresh(['attachments.uploader:id,name,email']);
        });
    }

    private function mapFileType(string $extension): NoticeAttachmentFileType
    {
        return match ($extension) {
            'jpg', 'jpeg', 'png', 'webp', 'gif' => NoticeAttachmentFileType::Image,
            'pdf' => NoticeAttachmentFileType::Pdf,
            'doc' => NoticeAttachmentFileType::Doc,
            'docx' => NoticeAttachmentFileType::Docx,
            'xls' => NoticeAttachmentFileType::Xls,
            'xlsx' => NoticeAttachmentFileType::Xlsx,
            'csv' => NoticeAttachmentFileType::Csv,
            'zip' => NoticeAttachmentFileType::Zip,
            default => NoticeAttachmentFileType::Other,
        };
    }
}
