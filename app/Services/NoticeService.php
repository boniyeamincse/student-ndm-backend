<?php

namespace App\Services;

use App\Enum\CommitteeStatus;
use App\Enum\MemberStatus;
use App\Enum\NoticeAudienceRuleType;
use App\Enum\NoticeAudienceType;
use App\Enum\NoticePriority;
use App\Enum\NoticeStatus;
use App\Enum\NoticeVisibility;
use App\Models\Member;
use App\Models\Notice;
use App\Models\NoticeAudienceRule;
use App\Models\NoticeStatusHistory;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NoticeService
{
    public function __construct(private readonly NoticeAttachmentService $attachmentService)
    {
    }

    public function list(array $filters): LengthAwarePaginator
    {
        $query = Notice::query()->with($this->listRelations());

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%")
                    ->orWhere('notice_no', 'like', "%{$search}%");
            });
        }

        foreach (['notice_type', 'priority', 'status', 'visibility', 'audience_type', 'committee_id', 'author_id', 'approver_id'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        foreach (['is_pinned', 'requires_acknowledgement'] as $field) {
            if (array_key_exists($field, $filters) && $filters[$field] !== null) {
                $query->where($field, (bool) $filters[$field]);
            }
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $active = (bool) $filters['is_active'];
            if ($active) {
                $query->where('status', NoticeStatus::Published)
                    ->where(function ($q) {
                        $q->whereNull('publish_at')->orWhere('publish_at', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    });
            } else {
                $query->where(function ($q) {
                    $q->where('status', '!=', NoticeStatus::Published)
                        ->orWhere('publish_at', '>', now())
                        ->orWhere('expires_at', '<=', now());
                });
            }
        }

        foreach ([
            'publish_from' => ['publish_at', '>='],
            'publish_to' => ['publish_at', '<='],
            'expires_from' => ['expires_at', '>='],
            'expires_to' => ['expires_at', '<='],
            'created_from' => ['created_at', '>='],
            'created_to' => ['created_at', '<='],
        ] as $key => [$column, $op]) {
            if (! empty($filters[$key])) {
                $query->whereDate($column, $op, $filters[$key]);
            }
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        $query->orderByDesc('is_pinned');
        if ($sortBy === 'priority') {
            $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low') {$sortDir}");
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        return $query->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function create(array $data, int $actorId): Notice
    {
        return DB::transaction(function () use ($data, $actorId) {
            $actor = User::findOrFail($actorId);
            $status = NoticeStatus::from($data['status'] ?? NoticeStatus::Draft->value);

            $this->assertPublishPermission($actor, $status);
            $this->validateAudienceContext($data['audience_type'], $data['committee_id'] ?? null);
            $this->validateCommittee($data['committee_id'] ?? null);

            $publishAt = $status === NoticeStatus::Published
                ? ($data['publish_at'] ?? now())
                : ($data['publish_at'] ?? null);

            $expiresAt = $data['expires_at'] ?? null;
            if ($publishAt && $expiresAt && strtotime((string) $expiresAt) <= strtotime((string) $publishAt)) {
                throw ValidationException::withMessages([
                    'expires_at' => ['Expiry must be after publish_at.'],
                ]);
            }

            $isPinned = (bool) ($data['is_pinned'] ?? false);
            $this->assertPinRules($isPinned, $status);

            $imagePath = $this->storeFeaturedImage($data['featured_image'] ?? null);

            $notice = Notice::create([
                'notice_no' => $this->generateNoticeNo(),
                'title' => trim($data['title']),
                'slug' => $this->generateUniqueSlug($data['slug'] ?? $data['title']),
                'summary' => $data['summary'] ?? null,
                'content' => $data['content'],
                'notice_type' => $data['notice_type'],
                'priority' => $data['priority'],
                'status' => $status,
                'visibility' => $data['visibility'],
                'audience_type' => $data['audience_type'],
                'committee_id' => $data['committee_id'] ?? null,
                'author_id' => $data['author_id'] ?? $actorId,
                'approver_id' => $status === NoticeStatus::Published ? ($data['approver_id'] ?? $actorId) : ($data['approver_id'] ?? null),
                'featured_image' => $imagePath,
                'featured_image_alt' => $data['featured_image_alt'] ?? null,
                'publish_at' => $publishAt,
                'expires_at' => $expiresAt,
                'is_pinned' => $isPinned,
                'requires_acknowledgement' => (bool) ($data['requires_acknowledgement'] ?? false),
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'meta_keywords' => $data['meta_keywords'] ?? null,
                'last_edited_at' => now(),
                'created_by' => $actorId,
            ]);

            $this->recordStatusHistory($notice, null, $status, $actorId, 'Initial status set on create.');
            $this->syncAudienceRules($notice, $data['audience_rules'] ?? []);

            if (! empty($data['attachments']) && is_array($data['attachments'])) {
                $this->attachmentService->addAttachments($notice, $data['attachments'], $actorId);
            }

            Log::info('Notice created', ['notice_id' => $notice->id, 'actor_id' => $actorId]);

            return $notice->fresh($this->detailRelations());
        });
    }

    public function detail(int $id): Notice
    {
        return Notice::with($this->detailRelations())->findOrFail($id);
    }

    public function update(Notice $notice, array $data, int $actorId): Notice
    {
        return DB::transaction(function () use ($notice, $data, $actorId) {
            $status = $notice->status;

            $this->validateAudienceContext(
                $data['audience_type'] ?? $notice->audience_type->value,
                $data['committee_id'] ?? $notice->committee_id
            );
            $this->validateCommittee($data['committee_id'] ?? $notice->committee_id);

            if (array_key_exists('title', $data) || array_key_exists('slug', $data)) {
                $base = $data['slug'] ?? ($data['title'] ?? $notice->title);
                $data['slug'] = $this->generateUniqueSlug($base, $notice->id);
            }

            if (! empty($data['featured_image']) && $data['featured_image'] instanceof UploadedFile) {
                $data['featured_image'] = $this->replaceFeaturedImage($notice->featured_image, $data['featured_image']);
            } else {
                unset($data['featured_image']);
            }

            $pinned = array_key_exists('is_pinned', $data) ? (bool) $data['is_pinned'] : (bool) $notice->is_pinned;
            $this->assertPinRules($pinned, $status);

            $effectivePublishAt = $data['publish_at'] ?? $notice->publish_at;
            $effectiveExpiresAt = $data['expires_at'] ?? $notice->expires_at;
            if ($effectivePublishAt && $effectiveExpiresAt && strtotime((string) $effectiveExpiresAt) <= strtotime((string) $effectivePublishAt)) {
                throw ValidationException::withMessages([
                    'expires_at' => ['Expiry must be after publish_at.'],
                ]);
            }

            $notice->update([
                ...$data,
                'updated_by' => $actorId,
                'last_edited_at' => now(),
            ]);

            if (array_key_exists('audience_rules', $data)) {
                $this->syncAudienceRules($notice, $data['audience_rules'] ?? []);
            }

            Log::info('Notice updated', ['notice_id' => $notice->id, 'actor_id' => $actorId]);

            return $notice->fresh($this->detailRelations());
        });
    }

    public function changeStatus(Notice $notice, NoticeStatus $newStatus, ?string $note, int $actorId): Notice
    {
        return DB::transaction(function () use ($notice, $newStatus, $note, $actorId) {
            $actor = User::findOrFail($actorId);
            $oldStatus = $notice->status;

            if ($oldStatus === $newStatus) {
                throw ValidationException::withMessages([
                    'status' => ['Notice already has this status.'],
                ]);
            }

            $this->assertStatusTransitionAllowed($oldStatus, $newStatus);
            $this->assertPublishPermission($actor, $newStatus);

            $payload = [
                'status' => $newStatus,
                'updated_by' => $actorId,
                'last_edited_at' => now(),
            ];

            if ($newStatus === NoticeStatus::Published) {
                if (! $notice->publish_at) {
                    $payload['publish_at'] = now();
                }
                if (! $notice->approver_id) {
                    $payload['approver_id'] = $actorId;
                }
            }

            if ($newStatus !== NoticeStatus::Published) {
                $payload['is_pinned'] = false;
            }

            $notice->update($payload);
            $this->recordStatusHistory($notice, $oldStatus, $newStatus, $actorId, $note);

            Log::info('Notice status changed', [
                'notice_id' => $notice->id,
                'old_status' => $oldStatus->value,
                'new_status' => $newStatus->value,
                'actor_id' => $actorId,
            ]);

            return $notice->fresh($this->detailRelations());
        });
    }

    public function updatePin(Notice $notice, bool $isPinned, int $actorId): Notice
    {
        $this->assertPinRules($isPinned, $notice->status);

        $notice->update([
            'is_pinned' => $isPinned,
            'updated_by' => $actorId,
            'last_edited_at' => now(),
        ]);

        Log::info('Notice pin updated', ['notice_id' => $notice->id, 'actor_id' => $actorId, 'is_pinned' => $isPinned]);

        return $notice->fresh($this->detailRelations());
    }

    public function addAttachments(Notice $notice, array $files, int $actorId): Notice
    {
        return $this->attachmentService->addAttachments($notice, $files, $actorId);
    }

    public function deleteAttachment(Notice $notice, int $attachmentId): Notice
    {
        return $this->attachmentService->deleteAttachment($notice, $attachmentId);
    }

    public function delete(Notice $notice, int $actorId): void
    {
        if ($notice->trashed()) {
            throw ValidationException::withMessages([
                'notice' => ['Notice is already deleted.'],
            ]);
        }

        $notice->update(['updated_by' => $actorId]);
        $notice->delete();

        Log::info('Notice deleted', ['notice_id' => $notice->id, 'actor_id' => $actorId]);
    }

    public function restore(Notice $notice, int $actorId): Notice
    {
        if (! $notice->trashed()) {
            throw ValidationException::withMessages([
                'notice' => ['Notice is not deleted.'],
            ]);
        }

        $notice->slug = $this->generateUniqueSlug($notice->slug, $notice->id);
        $notice->restore();
        $notice->update(['updated_by' => $actorId]);

        Log::info('Notice restored', ['notice_id' => $notice->id, 'actor_id' => $actorId]);

        return $notice->fresh($this->detailRelations());
    }

    public function listPublic(array $filters, bool $pinnedOnly = false): LengthAwarePaginator
    {
        $query = $this->basePublicQuery();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%");
            });
        }

        foreach (['notice_type', 'priority', 'committee_id'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if ($pinnedOnly || ($filters['pinned_only'] ?? false)) {
            $query->where('is_pinned', true);
        }

        $sortBy = $filters['sort_by'] ?? 'publish_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        $query->orderByDesc('is_pinned');
        if ($sortBy === 'priority') {
            $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low') {$sortDir}");
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        if (! empty($filters['limit'])) {
            return $query->paginate(min((int) $filters['limit'], 20));
        }

        return $query->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function detailPublicBySlug(string $slug): Notice
    {
        return $this->basePublicQuery()->where('slug', $slug)->firstOrFail();
    }

    public function listMemberVisible(User $user, array $filters): LengthAwarePaginator
    {
        $query = $this->memberVisibleQuery($user);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%");
            });
        }

        foreach (['notice_type', 'priority'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (array_key_exists('is_pinned', $filters) && $filters['is_pinned'] !== null) {
            $query->where('is_pinned', (bool) $filters['is_pinned']);
        }

        $sortBy = $filters['sort_by'] ?? 'publish_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        $query->orderByDesc('is_pinned');
        if ($sortBy === 'priority') {
            $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low') {$sortDir}");
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        return $query->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function detailMemberBySlug(User $user, string $slug): Notice
    {
        return $this->memberVisibleQuery($user)->where('slug', $slug)->firstOrFail();
    }

    public function summary(bool $includeCommitteeSummary = false): array
    {
        $query = Notice::query();

        $summary = [
            'total_notices' => (clone $query)->count(),
            'total_drafts' => (clone $query)->where('status', NoticeStatus::Draft)->count(),
            'total_pending_review' => (clone $query)->where('status', NoticeStatus::PendingReview)->count(),
            'total_published' => (clone $query)->where('status', NoticeStatus::Published)->count(),
            'total_unpublished' => (clone $query)->where('status', NoticeStatus::Unpublished)->count(),
            'total_archived' => (clone $query)->where('status', NoticeStatus::Archived)->count(),
            'total_expired' => (clone $query)->where('status', NoticeStatus::Expired)->count(),
            'total_pinned' => (clone $query)->where('is_pinned', true)->count(),
            'total_requires_acknowledgement' => (clone $query)->where('requires_acknowledgement', true)->count(),
            'notices_expiring_soon' => (clone $query)
                ->whereNotNull('expires_at')
                ->whereBetween('expires_at', [now(), now()->addDays(7)])
                ->count(),
            'published_this_month' => (clone $query)
                ->where('status', NoticeStatus::Published)
                ->whereBetween('publish_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'by_notice_type' => Notice::query()->select('notice_type', DB::raw('COUNT(*) as total'))->groupBy('notice_type')->pluck('total', 'notice_type')->toArray(),
            'by_priority' => Notice::query()->select('priority', DB::raw('COUNT(*) as total'))->groupBy('priority')->pluck('total', 'priority')->toArray(),
            'by_visibility' => Notice::query()->select('visibility', DB::raw('COUNT(*) as total'))->groupBy('visibility')->pluck('total', 'visibility')->toArray(),
            'by_audience_type' => Notice::query()->select('audience_type', DB::raw('COUNT(*) as total'))->groupBy('audience_type')->pluck('total', 'audience_type')->toArray(),
            'by_committee' => [],
        ];

        if ($includeCommitteeSummary) {
            $summary['by_committee'] = Notice::query()
                ->leftJoin('committees', 'committees.id', '=', 'notices.committee_id')
                ->select('notices.committee_id', 'committees.name', DB::raw('COUNT(notices.id) as total'))
                ->groupBy('notices.committee_id', 'committees.name')
                ->get()
                ->map(fn ($item) => [
                    'committee_id' => $item->committee_id,
                    'committee_name' => $item->name,
                    'total' => (int) $item->total,
                ])
                ->values()
                ->toArray();
        }

        return $summary;
    }

    public function publish(Notice $notice, int $actorId, ?string $note = null): Notice
    {
        return $this->changeStatus($notice, NoticeStatus::Published, $note, $actorId);
    }

    public function unpublish(Notice $notice, int $actorId, ?string $note = null): Notice
    {
        return $this->changeStatus($notice, NoticeStatus::Unpublished, $note, $actorId);
    }

    public function archive(Notice $notice, int $actorId, ?string $note = null): Notice
    {
        return $this->changeStatus($notice, NoticeStatus::Archived, $note, $actorId);
    }

    private function basePublicQuery()
    {
        return Notice::query()
            ->with(['committee:id,name,slug', 'attachments:id,notice_id,file_name,original_name,file_path,file_type,sort_order'])
            ->where('status', NoticeStatus::Published)
            ->where('visibility', NoticeVisibility::Public)
            ->where(function ($q) {
                $q->whereNull('publish_at')->orWhere('publish_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    private function memberVisibleQuery(User $user): Builder
    {
        $member = Member::query()->where('user_id', $user->id)->first();
        $memberId = $member?->id;
        $isActiveMember = $member?->status === MemberStatus::Active;

        $assignmentQuery = DB::table('committee_member_assignments')->whereNull('deleted_at')->where('is_active', true);
        $committeeIds = [];
        $leadership = false;
        $positionIds = [];
        $assignmentTypes = [];

        if ($memberId) {
            $memberAssignments = (clone $assignmentQuery)
                ->where('member_id', $memberId)
                ->select('committee_id', 'is_leadership', 'position_id', 'assignment_type')
                ->get();

            $committeeIds = $memberAssignments->pluck('committee_id')->filter()->unique()->values()->all();
            $leadership = $memberAssignments->contains(fn ($a) => (bool) $a->is_leadership === true);
            $positionIds = $memberAssignments->pluck('position_id')->filter()->unique()->values()->all();
            $assignmentTypes = $memberAssignments->pluck('assignment_type')->filter()->unique()->values()->all();
        }

        $roleNames = $user->roles->pluck('name')->filter()->values()->all();
        $isPrivilegedInternalViewer = $user->hasRole('superadmin') || $user->hasRole('admin') || $user->can('notice.view');

        return Notice::query()
            ->with(['committee:id,name,slug', 'attachments:id,notice_id,file_name,original_name,file_path,file_type,sort_order'])
            ->where('status', NoticeStatus::Published)
            ->where(function ($q) {
                $q->whereNull('publish_at')->orWhere('publish_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) use ($memberId, $isActiveMember, $committeeIds, $leadership, $isPrivilegedInternalViewer, $positionIds, $assignmentTypes, $roleNames) {
                $q->orWhere(function ($sub) {
                    $sub->where('visibility', NoticeVisibility::Public)
                        ->where('audience_type', NoticeAudienceType::All);
                });

                if ($isActiveMember) {
                    $q->orWhere(function ($sub) {
                        $sub->where('visibility', NoticeVisibility::MembersOnly)
                            ->whereIn('audience_type', [NoticeAudienceType::All, NoticeAudienceType::MembersOnly]);
                    });
                }

                if ($isPrivilegedInternalViewer) {
                    $q->orWhere(function ($sub) {
                        $sub->where('visibility', NoticeVisibility::Internal)
                            ->where('audience_type', NoticeAudienceType::All);
                    });
                }

                if (! empty($committeeIds)) {
                    $q->orWhere(function ($sub) use ($committeeIds) {
                        $sub->where('audience_type', NoticeAudienceType::CommitteeSpecific)
                            ->whereIn('committee_id', $committeeIds);
                    });
                }

                if ($leadership) {
                    $q->orWhere('audience_type', NoticeAudienceType::LeadershipOnly);
                }

                $q->orWhere(function ($sub) use ($memberId, $committeeIds, $positionIds, $assignmentTypes, $roleNames) {
                    $sub->where('audience_type', NoticeAudienceType::Custom)
                        ->whereHas('audienceRules', function ($rule) use ($memberId, $committeeIds, $positionIds, $assignmentTypes, $roleNames) {
                            $rule->where(function ($r) use ($memberId, $committeeIds, $positionIds, $assignmentTypes, $roleNames) {
                                if (! empty($roleNames)) {
                                    $r->orWhere(function ($x) use ($roleNames) {
                                        $x->where('rule_type', NoticeAudienceRuleType::Role)
                                            ->whereIn('rule_value', $roleNames);
                                    });
                                }

                                if ($memberId) {
                                    $r->orWhere(function ($x) use ($memberId) {
                                        $x->where('rule_type', NoticeAudienceRuleType::Member)
                                            ->where('rule_value', (string) $memberId);
                                    });
                                }

                                if (! empty($committeeIds)) {
                                    $r->orWhere(function ($x) use ($committeeIds) {
                                        $x->where('rule_type', NoticeAudienceRuleType::Committee)
                                            ->whereIn('rule_value', array_map('strval', $committeeIds));
                                    });
                                }

                                if (! empty($positionIds)) {
                                    $r->orWhere(function ($x) use ($positionIds) {
                                        $x->where('rule_type', NoticeAudienceRuleType::Position)
                                            ->whereIn('rule_value', array_map('strval', $positionIds));
                                    });
                                }

                                if (! empty($assignmentTypes)) {
                                    $r->orWhere(function ($x) use ($assignmentTypes) {
                                        $x->where('rule_type', NoticeAudienceRuleType::AssignmentType)
                                            ->whereIn('rule_value', array_map('strval', $assignmentTypes));
                                    });
                                }
                            });
                        });
                });
            });
    }

    private function assertPublishPermission(User $actor, NoticeStatus $targetStatus): void
    {
        if ($targetStatus === NoticeStatus::Published && ! $actor->can('notice.publish')) {
            throw ValidationException::withMessages([
                'status' => ['You are not authorized to publish notices.'],
            ]);
        }
    }

    private function assertPinRules(bool $isPinned, NoticeStatus $status): void
    {
        if ($isPinned && $status !== NoticeStatus::Published) {
            throw ValidationException::withMessages([
                'is_pinned' => ['Only published notices can be pinned.'],
            ]);
        }
    }

    private function assertStatusTransitionAllowed(NoticeStatus $from, NoticeStatus $to): void
    {
        $allowed = match ($from) {
            NoticeStatus::Draft => [NoticeStatus::PendingReview, NoticeStatus::Published, NoticeStatus::Archived],
            NoticeStatus::PendingReview => [NoticeStatus::Published, NoticeStatus::Draft, NoticeStatus::Archived],
            NoticeStatus::Published => [NoticeStatus::Unpublished, NoticeStatus::Archived, NoticeStatus::Expired],
            NoticeStatus::Unpublished => [NoticeStatus::Published, NoticeStatus::Archived, NoticeStatus::Draft],
            NoticeStatus::Archived => [NoticeStatus::Draft, NoticeStatus::Unpublished],
            NoticeStatus::Expired => [NoticeStatus::Draft, NoticeStatus::Unpublished, NoticeStatus::Archived],
        };

        if (! in_array($to, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => ["Invalid status transition from {$from->value} to {$to->value}."],
            ]);
        }
    }

    private function validateAudienceContext(string $audienceType, ?int $committeeId): void
    {
        if ($audienceType === NoticeAudienceType::CommitteeSpecific->value && ! $committeeId) {
            throw ValidationException::withMessages([
                'committee_id' => ['committee_id is required when audience_type is committee_specific.'],
            ]);
        }
    }

    private function validateCommittee(?int $committeeId): void
    {
        if (! $committeeId) {
            return;
        }

        $committee = \App\Models\Committee::findOrFail($committeeId);
        if ($committee->status === CommitteeStatus::Dissolved || $committee->status === CommitteeStatus::Archived) {
            throw ValidationException::withMessages([
                'committee_id' => ['Cannot target a dissolved or archived committee.'],
            ]);
        }
    }

    private function syncAudienceRules(Notice $notice, array $rules): void
    {
        NoticeAudienceRule::query()->where('notice_id', $notice->id)->delete();

        foreach ($rules as $rule) {
            if (empty($rule['rule_type']) || empty($rule['rule_value'])) {
                continue;
            }

            NoticeAudienceRule::create([
                'notice_id' => $notice->id,
                'rule_type' => $rule['rule_type'],
                'rule_value' => (string) $rule['rule_value'],
                'created_at' => now(),
            ]);
        }
    }

    private function recordStatusHistory(
        Notice $notice,
        ?NoticeStatus $oldStatus,
        NoticeStatus $newStatus,
        ?int $actorId,
        ?string $note
    ): void {
        NoticeStatusHistory::create([
            'notice_id' => $notice->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $actorId,
            'note' => $note,
            'created_at' => now(),
        ]);
    }

    private function generateNoticeNo(): string
    {
        $year = now()->year;
        $last = Notice::withTrashed()
            ->whereYear('created_at', $year)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('notice_no');

        $next = 1;
        if ($last && preg_match('/NTC-\d{4}-(\d{6})/', $last, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return sprintf('NTC-%d-%06d', $year, $next);
    }

    private function generateUniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'notice';
        $slug = $base;
        $i = 2;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $query = Notice::withTrashed()->where('slug', $slug);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    private function storeFeaturedImage(mixed $image): ?string
    {
        if (! $image instanceof UploadedFile) {
            return null;
        }

        return $image->store('notices/featured-images', 'public');
    }

    private function replaceFeaturedImage(?string $oldPath, UploadedFile $newImage): string
    {
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return $newImage->store('notices/featured-images', 'public');
    }

    private function listRelations(): array
    {
        return [
            'committee:id,name,slug',
            'author:id,name,email',
        ];
    }

    private function detailRelations(): array
    {
        return [
            ...$this->listRelations(),
            'approver:id,name,email',
            'creator:id,name,email',
            'updater:id,name,email',
            'attachments.uploader:id,name,email',
            'audienceRules',
            'statusHistories.changedByUser:id,name,email',
        ];
    }
}
