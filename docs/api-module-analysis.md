# API Module Analysis

## Overview

This backend is a Laravel 13 REST API organized under `/api/v1`, with a small system route set under `/api`.

| Area | Route prefix | Main access model | Core purpose |
| --- | --- | --- | --- |
| System | `/api` | Public | Health check |
| Auth | `/api/v1/auth` | Public + authenticated | Registration, login, password lifecycle, social auth |
| Membership | `/api/v1/membership` | Public + admin | Applicant onboarding and approval workflow |
| Public content | `/api/v1/public` | Public | Published posts and notices |
| Member notices | `/api/v1/member` | Authenticated member/user | Audience-filtered internal notices |
| Self service | `/api/v1/me` | Authenticated user with self-service permissions | Profile, settings, personal hierarchy views |
| Admin operations | `/api/v1/admin` | Authenticated admin/superadmin with policies or permissions | Master data, content, hierarchy, role management |
| Dashboard | `/api/v1/dashboard` | Authenticated user with dashboard permissions | KPI cards, charts, pending items |
| Reports | `/api/v1/reports` | Authenticated user with report permissions | Aggregated operational reporting |

## Cross-cutting business patterns

- **Auth** uses Sanctum bearer tokens.
- **Authorization** is mixed between Laravel policies (`$this->authorize(...)`) and explicit permission checks (`user()->can(...)`) backed by Spatie Permission.
- **Soft delete + restore** is the default lifecycle for most admin-managed records.
- **Status history tables** are used in the workflow-heavy modules so state changes remain auditable.
- **Number generation** is business-significant across modules: applications, members, committees, assignments, relations, posts, notices, and profile update requests all get generated reference numbers.
- **Service layer owns business rules**. Controllers are mostly validation, authorization, and response shaping.

## Module 1: System and authentication

**Key files**

- `routes/api.php`
- `app/Http/Controllers/Api/V1/AuthController.php`
- `app/Services/AuthService.php`
- `app/Models/User.php`

**Endpoints**

- `GET /api/health`
- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/forgot-password`
- `POST /api/v1/auth/reset-password`
- `GET /api/v1/auth/social/{provider}/redirect`
- `GET /api/v1/auth/social/{provider}/callback`
- `GET /api/v1/auth/me`
- `POST /api/v1/auth/logout`
- `POST /api/v1/auth/logout-all`
- `PUT /api/v1/auth/change-password`

**Business logic**

- Public auth routes are throttled at `10/min`.
- Registration creates a lightweight **active** user immediately, auto-generates a unique username from email, and assigns the `member` role if that role exists.
- Login accepts either **email or phone** and intentionally returns the same failure message for missing user vs wrong password to reduce user enumeration risk.
- User login is gated by `UserStatus::canLogin()`, so only **active** users can authenticate.
- Social auth supports only **Google** and **Facebook**. First-time social login auto-provisions a user, sets `email_verified_at`, and returns a `requires_profile_completion` hint to the frontend.
- Password reset uses Laravel broker flow and always returns a generic response for forgot-password.
- Both **reset password** and **change password** revoke tokens; change password revokes **all** tokens, including the current one.

## Module 2: Membership application and approval

**Key files**

- `app/Http/Controllers/Api/V1/MembershipApplicationController.php`
- `app/Http/Controllers/Api/V1/AdminMembershipApplicationController.php`
- `app/Services/MembershipApplicationService.php`
- `app/Models/MembershipApplication.php`
- `app/Models/ApplicationStatusHistory.php`
- `app/Enum/ApplicationStatus.php`

**Endpoints**

- `POST /api/v1/membership/apply`
- `GET /api/v1/admin/membership-applications`
- `GET /api/v1/admin/membership-applications/{id}`
- `PUT /api/v1/admin/membership-applications/{id}/review`
- `PUT /api/v1/admin/membership-applications/{id}/approve`
- `PUT /api/v1/admin/membership-applications/{id}/reject`
- `PUT /api/v1/admin/membership-applications/{id}/hold`
- `DELETE /api/v1/admin/membership-applications/{id}`
- `PUT /api/v1/admin/membership-applications/{id}/restore`

**Business logic**

- Public submission is throttled at `5/min` per IP.
- Submission blocks duplicate **active** applications for the same email or mobile. “Active” here means `pending`, `under_review`, or `on_hold`.
- On submit, the system stores the applicant photo, generates `application_no`, records IP/source, sets status to `pending`, writes a history row, and attempts to send an acknowledgement notification.
- Review moves an application to `under_review` unless it is already finalized.
- Approve is the most important workflow:
  - runs inside a DB transaction,
  - marks the application approved,
  - resolves or creates a `User`,
  - creates a linked `Member`,
  - assigns the `member` role,
  - then, outside the transaction, tries to send a password setup link and approval notification.
- If email and mobile resolve to **different existing users**, approval is blocked for manual resolution.
- Rejected applications cannot be approved directly; approved applications cannot be rejected or put on hold.
- Status changes are audited through `application_status_histories`.

## Module 3: Members

**Key files**

- `app/Http/Controllers/AdminMemberController.php`
- `app/Services/MemberService.php`
- `app/Models/Member.php`
- `app/Models/MemberStatusHistory.php`
- `app/Enum/MemberStatus.php`

**Endpoints**

- `GET /api/v1/admin/members-summary`
- `GET /api/v1/admin/members`
- `GET /api/v1/admin/members/{member}`
- `PUT /api/v1/admin/members/{member}`
- `PATCH /api/v1/admin/members/{member}/status`
- `DELETE /api/v1/admin/members/{member}`
- `PUT /api/v1/admin/members/{member}/restore`

**Business logic**

- Member records are the operational profile created from approved membership applications.
- Member updates can replace the stored photo and keep `users.name` / `users.email` synchronized when those fields change.
- Status changes write `last_status_changed_at`, `status_note`, and a `member_status_histories` row.
- Admin can optionally set `revoke_access=true` on status change, which inactivates the linked user and deletes tokens.
- Summary endpoints aggregate counts by status, gender, and optionally division.

## Module 4: Committee types and committees

**Key files**

- `app/Http/Controllers/AdminCommitteeTypeController.php`
- `app/Http/Controllers/AdminCommitteeController.php`
- `app/Services/CommitteeTypeService.php`
- `app/Services/CommitteeService.php`
- `app/Models/CommitteeType.php`
- `app/Models/Committee.php`
- `app/Models/CommitteeStatusHistory.php`

**Endpoints**

- `GET|POST /api/v1/admin/committee-types`
- `GET|PUT|DELETE /api/v1/admin/committee-types/{committeeType}`
- `PUT /api/v1/admin/committee-types/{committeeType}/restore`
- `GET /api/v1/admin/committees-summary`
- `GET /api/v1/admin/committees-tree`
- `GET|POST /api/v1/admin/committees`
- `GET|PUT|DELETE /api/v1/admin/committees/{committee}`
- `PATCH /api/v1/admin/committees/{committee}/status`
- `PUT /api/v1/admin/committees/{committee}/restore`

**Business logic**

- Committee types define **hierarchy order** and are used to validate committee parent/child relationships.
- Default type slugs (`central`, `division`, `district`, `upazila`, `union`) cannot be deleted.
- Committee creation validates:
  - correct parent level,
  - correct creation order,
  - required geographic scope by hierarchy level,
  - duplicate name/type/location conflicts,
  - cycle safety on updates.
- Non-central committees require a parent committee of the immediately preceding hierarchy level.
- Committees get generated `committee_no`, unique slug, and type-based code.
- Committee status changes are audited; `dissolved` and `archived` force `is_current=false`.
- Committees cannot be deleted while active child committees still exist.
- The tree endpoint is a true hierarchy builder, not just a grouped list.

## Module 5: Positions

**Key files**

- `app/Http/Controllers/AdminPositionController.php`
- `app/Services/PositionService.php`
- `app/Models/Position.php`
- `app/Models/PositionStatusHistory.php`
- `app/Enum/PositionScope.php`

**Endpoints**

- `GET /api/v1/admin/positions-summary`
- `GET|POST /api/v1/admin/positions`
- `GET|PUT|DELETE /api/v1/admin/positions/{position}`
- `PATCH /api/v1/admin/positions/{position}/status`
- `PUT /api/v1/admin/positions/{position}/restore`

**Business logic**

- Positions are reusable designations with ordering and leadership semantics.
- `scope=global` means usable everywhere; `scope=committee_specific` means the position must be mapped to one or more active committee types.
- Name and code uniqueness are enforced across active and soft-deleted records.
- Status change is modeled as active/inactive and audited in `position_status_histories`.
- When a position becomes global, committee-type mappings are cleared.

## Module 6: Committee member assignments

**Key files**

- `app/Http/Controllers/AdminCommitteeMemberAssignmentController.php`
- `app/Services/CommitteeMemberAssignmentService.php`
- `app/Models/CommitteeMemberAssignment.php`
- `app/Models/CommitteeMemberAssignmentHistory.php`
- `app/Models/CommitteeMemberPositionHistory.php`
- `app/Enum/AssignmentStatus.php`
- `app/Enum/AssignmentType.php`

**Endpoints**

- `GET /api/v1/admin/committee-member-assignments-summary`
- `GET|POST /api/v1/admin/committee-member-assignments`
- `GET|PUT|DELETE /api/v1/admin/committee-member-assignments/{assignment}`
- `PATCH /api/v1/admin/committee-member-assignments/{assignment}/status`
- `POST /api/v1/admin/committee-member-assignments/{assignment}/transfer`
- `PUT /api/v1/admin/committee-member-assignments/{assignment}/restore`
- `GET /api/v1/admin/committees/{committeeId}/members`
- `GET /api/v1/admin/committees/{committeeId}/office-bearers`
- `GET /api/v1/admin/members/{memberId}/committee-assignments`

**Business logic**

- Only **active members** can be assigned.
- Assignments are allowed only for committees that are **active and current**.
- If a position is committee-specific, it must be valid for the committee’s type.
- `office_bearer` assignments require a position.
- A member can hold only **one active assignment per committee** at a time.
- A member can hold only **one active primary assignment per committee type level**.
- `AssignmentStatus` and `is_active` are coupled through service rules: non-`active` statuses cannot remain active.
- Transfer does not mutate the existing row into the new assignment; it creates a **new assignment record** and, for move semantics, completes/deactivates the old assignment.
- Assignment history captures creation, update, status changes, primary flag changes, activation/deactivation, transfer, delete, restore, and position changes.
- Office-bearer lists are sorted with leadership and hierarchy rank in mind, not simple creation order.

## Module 7: Reporting hierarchy

**Key files**

- `app/Http/Controllers/AdminMemberReportingRelationController.php`
- `app/Services/MemberReportingRelationService.php`
- `app/Models/MemberReportingRelation.php`
- `app/Models/MemberReportingRelationHistory.php`
- `app/Enum/ReportingRelationType.php`

**Endpoints**

- `GET /api/v1/admin/member-reporting-relations-summary`
- `GET|POST /api/v1/admin/member-reporting-relations`
- `GET|PUT|DELETE /api/v1/admin/member-reporting-relations/{id}`
- `PATCH /api/v1/admin/member-reporting-relations/{id}/status`
- `PUT /api/v1/admin/member-reporting-relations/{id}/restore`
- `GET /api/v1/admin/committee-member-assignments/{assignmentId}/leader`
- `GET /api/v1/admin/committee-member-assignments/{assignmentId}/subordinates`
- `GET /api/v1/admin/committees/{committeeId}/hierarchy-tree`

**Business logic**

- Reporting relations connect a **subordinate assignment** to a **superior assignment**.
- The service currently requires both assignments to belong to the **same committee**.
- Active relations require both assignments to be active.
- `is_primary` is only allowed for `direct_report`.
- Duplicate active relations are blocked.
- Circular hierarchies are blocked by explicit cycle detection.
- The hierarchy-tree endpoint builds a nested org structure from assignments plus active relations and can optionally include orphaned assignments.
- Leader lookup defaults to the active, primary, direct-report relation unless the caller relaxes filters.

## Module 8: Posts and post categories

**Key files**

- `app/Http/Controllers/AdminPostCategoryController.php`
- `app/Http/Controllers/AdminPostController.php`
- `app/Http/Controllers/PublicPostController.php`
- `app/Services/PostCategoryService.php`
- `app/Services/PostService.php`
- `app/Models/Post.php`
- `app/Models/PostCategory.php`
- `app/Models/PostStatusHistory.php`
- `app/Enum/PostStatus.php`
- `app/Enum/PostVisibility.php`

**Endpoints**

- Public:
  - `GET /api/v1/public/posts`
  - `GET /api/v1/public/posts/{slug}`
  - `GET /api/v1/public/post-categories`
  - `GET /api/v1/public/featured-posts`
  - `GET /api/v1/public/news`
  - `GET /api/v1/public/blogs`
- Admin categories:
  - `GET|POST /api/v1/admin/post-categories`
  - `GET|PUT|DELETE /api/v1/admin/post-categories/{id}`
  - `PATCH /api/v1/admin/post-categories/{id}/status`
  - `PUT /api/v1/admin/post-categories/{id}/restore`
- Admin posts:
  - `GET /api/v1/admin/posts-summary`
  - `GET|POST /api/v1/admin/posts`
  - `GET|PUT|DELETE /api/v1/admin/posts/{id}`
  - `PATCH /api/v1/admin/posts/{id}/status`
  - `PATCH /api/v1/admin/posts/{id}/feature`
  - `POST /api/v1/admin/posts/{id}/publish`
  - `POST /api/v1/admin/posts/{id}/unpublish`
  - `POST /api/v1/admin/posts/{id}/archive`
  - `PUT /api/v1/admin/posts/{id}/restore`

**Business logic**

- Public content only shows posts that are:
  - `published`,
  - `visibility=public`,
  - and either unpublished date is null or already reached.
- Public detail increments `view_count`.
- Publishing a post requires `post.publish`.
- Allowed status transitions are explicit and enforced in service code.
- Featured/homepage rules:
  - only published posts can be featured,
  - homepage placement requires `visibility=public`.
- Publishing is blocked if the selected category is inactive.
- Committee-targeted posts require the committee to be active or current.
- Post categories cannot be deleted while used by published posts.

## Module 9: Notices

**Key files**

- `app/Http/Controllers/AdminNoticeController.php`
- `app/Http/Controllers/PublicNoticeController.php`
- `app/Http/Controllers/MemberNoticeController.php`
- `app/Services/NoticeService.php`
- `app/Services/NoticeAttachmentService.php`
- `app/Models/Notice.php`
- `app/Models/NoticeAttachment.php`
- `app/Models/NoticeAudienceRule.php`
- `app/Models/NoticeStatusHistory.php`
- `app/Enum/NoticeStatus.php`
- `app/Enum/NoticeAudienceType.php`
- `app/Enum/NoticeAudienceRuleType.php`

**Endpoints**

- Public:
  - `GET /api/v1/public/notices`
  - `GET /api/v1/public/notices/{slug}`
  - `GET /api/v1/public/pinned-notices`
- Member:
  - `GET /api/v1/member/notices`
  - `GET /api/v1/member/notices/{slug}`
- Admin:
  - `GET /api/v1/admin/notices-summary`
  - `GET|POST /api/v1/admin/notices`
  - `GET|PUT|DELETE /api/v1/admin/notices/{id}`
  - `PATCH /api/v1/admin/notices/{id}/status`
  - `PATCH /api/v1/admin/notices/{id}/pin`
  - `POST /api/v1/admin/notices/{id}/attachments`
  - `DELETE /api/v1/admin/notices/{id}/attachments/{attachmentId}`
  - `PUT /api/v1/admin/notices/{id}/restore`

**Business logic**

- Notices have both **visibility** and **audience** rules.
- Public queries only show notices that are:
  - `published`,
  - `visibility=public`,
  - inside the publish/expiry window.
- Pinned notices always sort first.
- Publishing requires `notice.publish`; pinning requires `pin` authorization on the notice.
- A notice can only be pinned when published.
- `committee_specific` audience requires `committee_id`.
- Dissolved or archived committees cannot be targeted.
- `custom` audience is built from `notice_audience_rules`, which can target:
  - role,
  - member,
  - committee,
  - position,
  - assignment type.
- Member-visible queries dynamically compute whether the current user qualifies through member status, active assignments, leadership flag, roles, positions, and assignment types.
- Attachment management is delegated to `NoticeAttachmentService`.

## Module 10: Self profile, account settings, and profile update requests

**Key files**

- `app/Http/Controllers/MeProfileController.php`
- `app/Http/Controllers/MeProfileUpdateRequestController.php`
- `app/Services/SelfProfileService.php`
- `app/Services/AccountSettingService.php`
- `app/Services/ProfileUpdateRequestService.php`
- `app/Models/AccountSetting.php`
- `app/Models/ProfileUpdateRequest.php`
- `app/Models/ProfileUpdateRequestHistory.php`

**Endpoints**

- `GET|PUT /api/v1/me/profile`
- `POST /api/v1/me/profile/photo`
- `GET|PUT /api/v1/me/account-settings`
- `GET /api/v1/me/member-overview`
- `GET /api/v1/me/committee-assignments`
- `GET /api/v1/me/leader`
- `GET /api/v1/me/subordinates`
- `GET /api/v1/me/profile-summary`
- `GET|POST /api/v1/me/profile-update-requests`
- `GET /api/v1/me/profile-update-requests/{id}`
- `POST /api/v1/me/profile-update-requests/{id}/cancel`
- Admin review:
  - `GET /api/v1/admin/profile-update-requests`
  - `GET /api/v1/admin/profile-update-requests/{id}`
  - `PATCH /api/v1/admin/profile-update-requests/{id}/approve`
  - `PATCH /api/v1/admin/profile-update-requests/{id}/reject`

**Business logic**

- Self profile reads merge `users`, `members`, roles, account settings, assignment counts, subordinate counts, and pending profile request counts.
- If the user has no `Member` yet, profile reads can fall back to the most recent membership application.
- Direct profile edits are intentionally restricted:
  - fields like `member_no`, `status`, hierarchy/assignment fields, and direct email change are blocked,
  - lower-risk fields such as phone, address, bio, and education details can be updated directly.
- Profile photo source of truth depends on the account:
  - linked member => stored on `members.photo`,
  - user without member => stored on `users.profile_photo`.
- Account settings are lazily created with defaults like `Asia/Dhaka`, email notifications enabled, and profile visibility set to members-only.
- Profile update requests:
  - store `requested_changes` JSON,
  - prevent identical duplicate pending requests of the same type,
  - move through `pending -> approved/rejected/cancelled`,
  - apply only whitelisted fields on approval,
  - enforce email uniqueness before approving email changes.

## Module 11: Roles, permissions, user-role sync, and admin menu

**Key files**

- `app/Http/Controllers/Api/V1/AdminRoleController.php`
- `app/Http/Controllers/Api/V1/AdminPermissionController.php`
- `app/Http/Controllers/Api/V1/AdminUserRoleController.php`
- `app/Http/Controllers/AdminMenuController.php`
- `app/Services/RoleService.php`
- `app/Services/PermissionService.php`
- `app/Services/UserRoleService.php`
- `app/Services/AdminMenuService.php`

**Endpoints**

- `GET /api/v1/admin/menu`
- `GET|POST /api/v1/admin/roles`
- `GET /api/v1/admin/roles/summary`
- `GET|PUT|DELETE /api/v1/admin/roles/{role}`
- `PATCH /api/v1/admin/roles/{role}/permissions`
- `GET /api/v1/admin/permissions`
- `GET /api/v1/admin/permissions/grouped`
- `GET /api/v1/admin/permissions/{permission}`
- `PATCH /api/v1/admin/users/{user}/roles`
- `GET /api/v1/admin/users/lookup`

**Business logic**

- The system uses Spatie roles/permissions.
- Protected system roles are `superadmin`, `admin`, and `member`.
- Protected roles cannot be deleted; system roles cannot be renamed.
- `superadmin` cannot be stripped down to zero permissions.
- User-role sync protects against:
  - removing `superadmin` from the **last superadmin**,
  - removing your own superadmin role.
- Permissions are grouped into UI-friendly modules by prefix, such as `dashboard`, `membership`, `member`, `committee`, `position`, `hierarchy`, `post`, `notice`, `self`, `profile`, `report`, and `role`.
- The admin menu is not static for all admins; `AdminMenuService` builds a base tree and removes nodes the user lacks permission to see.

## Module 12: Dashboard and reports

**Key files**

- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/ReportController.php`
- `app/Services/DashboardService.php`
- `app/Services/ActivityFeedService.php`
- `app/Services/AnalyticsService.php`
- `app/Services/ReportService.php`

**Endpoints**

- Dashboard:
  - `GET /api/v1/dashboard`
  - `GET /api/v1/dashboard/superadmin`
  - `GET /api/v1/dashboard/admin`
  - `GET /api/v1/dashboard/member`
  - `GET /api/v1/dashboard/stats`
  - `GET /api/v1/dashboard/charts`
  - `GET /api/v1/dashboard/recent-activities`
  - `GET /api/v1/dashboard/pending-items`
  - `GET /api/v1/dashboard/latest-content`
- Reports:
  - `GET /api/v1/reports/overview`
  - `GET /api/v1/reports/membership-applications`
  - `GET /api/v1/reports/members`
  - `GET /api/v1/reports/committees`
  - `GET /api/v1/reports/committee-assignments`
  - `GET /api/v1/reports/reporting-hierarchy`
  - `GET /api/v1/reports/posts`
  - `GET /api/v1/reports/notices`
  - `GET /api/v1/reports/profile-update-requests`
  - `GET /api/v1/reports/activity-summary`

**Business logic**

- Dashboard payloads are **role-shaped**:
  - admin/superadmin get aggregate cards, pending items, recent activity, latest content, and quick links,
  - members get personalized cards and pending items.
- Admin cards are cached for **5 minutes**; member cards are intentionally not cached because they are personalized.
- Pending items encode operational queue logic, such as pending membership applications, pending profile requests, expiring notices, and content waiting for review.
- Chart endpoints are admin-only and use `AnalyticsService` to build trend datasets.
- Report endpoints are permission-gated individually, not by one broad “reports” check.
- `ReportService` returns a consistent structure across modules:
  - `filters`,
  - `summary`,
  - `groups`,
  - `items`,
  - `meta`.
- Reports use `DB::table()` aggregation for leaner payload generation and cover the same main business domains as the API.

## Practical architecture summary

- The API is organized around a real operating model: **onboarding -> membership -> committee structure -> assignment -> reporting hierarchy -> content -> administration**.
- The most important workflows are the ones with explicit state machines and audit history:
  - membership applications,
  - member status,
  - committee status,
  - positions active state,
  - assignments and transfers,
  - reporting relations,
  - posts,
  - notices,
  - profile update requests.
- The deepest business logic lives in:
  - `MembershipApplicationService`,
  - `CommitteeService`,
  - `CommitteeMemberAssignmentService`,
  - `MemberReportingRelationService`,
  - `PostService`,
  - `NoticeService`,
  - `ProfileUpdateRequestService`.
