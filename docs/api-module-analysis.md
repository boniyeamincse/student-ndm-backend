# API Module Analysis

## Overview

This backend is a Laravel-based REST API organized under `/api/v1` with a small set of system routes under `/api`.

- Framework: Laravel
- Authentication: Laravel Sanctum bearer token
- Authorization: Laravel Policies + Spatie Permission
- Response style: standardized JSON envelope
- Total API routes discovered: `163`

Base URLs:

- `GET /api/health`
- Versioned API base: `/api/v1`

Standard response shape:

```json
{
  "success": true,
  "message": "Request successful.",
  "data": {},
  "meta": {}
}
```

The response helper is defined in `app/Traits/ApiResponse.php`.

## Module 1: System and Auth

Primary files:

- `routes/api.php`
- `routes/api_v1.php`
- `app/Http/Controllers/Api/V1/AuthController.php`
- `app/Services/AuthService.php`

Endpoints:

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

Behavior:

- Public auth endpoints are rate-limited with `throttle:10,1`.
- Login supports email or phone via request logic.
- Social login supports `google` and `facebook`.
- Password reset responses are intentionally generic to reduce email enumeration risk.
- Password change and password reset revoke tokens for security.

Observations:

- Auth flow is clean and separated into controller + service layers.
- Social callback redirects to the frontend with token and next-step hints.
- Authenticated user profile is exposed through `/auth/me`.

## Module 2: Membership Application

Primary files:

- `app/Http/Controllers/Api/V1/MembershipApplicationController.php`
- `app/Http/Controllers/Api/V1/AdminMembershipApplicationController.php`
- `app/Services/MembershipApplicationService.php`

Public endpoint:

- `POST /api/v1/membership/apply`

Admin endpoints:

- `GET /api/v1/admin/membership-applications`
- `GET /api/v1/admin/membership-applications/{id}`
- `PUT /api/v1/admin/membership-applications/{id}/review`
- `PUT /api/v1/admin/membership-applications/{id}/approve`
- `PUT /api/v1/admin/membership-applications/{id}/reject`
- `PUT /api/v1/admin/membership-applications/{id}/hold`
- `DELETE /api/v1/admin/membership-applications/{id}`
- `PUT /api/v1/admin/membership-applications/{id}/restore`

Behavior:

- Public submission is rate-limited with `throttle:5,1`.
- Approval flow appears to create both a `user` and a `member`.
- Status history and review actions are modeled explicitly.

Observations:

- This module looks like a core onboarding workflow.
- State transitions are important here and already have some test coverage.

## Module 3: Public Content

Primary files:

- `app/Http/Controllers/PublicPostController.php`
- `app/Http/Controllers/PublicNoticeController.php`
- `app/Services/PostService.php`
- `app/Services/NoticeService.php`

Post endpoints:

- `GET /api/v1/public/posts`
- `GET /api/v1/public/posts/{slug}`
- `GET /api/v1/public/post-categories`
- `GET /api/v1/public/featured-posts`
- `GET /api/v1/public/news`
- `GET /api/v1/public/blogs`

Notice endpoints:

- `GET /api/v1/public/notices`
- `GET /api/v1/public/notices/{slug}`
- `GET /api/v1/public/pinned-notices`

Behavior:

- Public content is read-only.
- Post listings support filtered slices such as featured, news, and blogs.
- Category listing is independent and only returns active categories.

Observations:

- Public API design is straightforward and frontend-friendly.
- Slug-based detail routes are good for SEO and public URLs.

## Module 4: Member Self-Service (`/me`)

Primary files:

- `app/Http/Controllers/MeProfileController.php`
- `app/Http/Controllers/MeProfileUpdateRequestController.php`
- `app/Services/SelfProfileService.php`
- `app/Services/AccountSettingService.php`
- `app/Services/ProfileUpdateRequestService.php`

Endpoints:

- `GET /api/v1/me/profile`
- `PUT /api/v1/me/profile`
- `POST /api/v1/me/profile/photo`
- `GET /api/v1/me/account-settings`
- `PUT /api/v1/me/account-settings`
- `GET /api/v1/me/member-overview`
- `GET /api/v1/me/committee-assignments`
- `GET /api/v1/me/leader`
- `GET /api/v1/me/subordinates`
- `GET /api/v1/me/profile-summary`
- `GET /api/v1/me/profile-update-requests`
- `POST /api/v1/me/profile-update-requests`
- `GET /api/v1/me/profile-update-requests/{id}`
- `POST /api/v1/me/profile-update-requests/{id}/cancel`

Behavior:

- These routes require `auth:sanctum`.
- Many actions rely on fine-grained permissions such as `self.profile.view`.
- Account settings are created on demand if missing.
- Profile changes can go through a request-based review flow, not only direct edits.

Observations:

- This module is well-scoped for member self-service.
- The split between direct profile updates and profile update requests suggests approval-sensitive fields.

## Module 5: Member Management

Primary files:

- `app/Http/Controllers/AdminMemberController.php`
- `app/Services/MemberService.php`
- `app/Policies/MemberPolicy.php`

Endpoints:

- `GET /api/v1/admin/members-summary`
- `GET /api/v1/admin/members`
- `GET /api/v1/admin/members/{member}`
- `PUT /api/v1/admin/members/{member}`
- `PATCH /api/v1/admin/members/{member}/status`
- `DELETE /api/v1/admin/members/{member}`
- `PUT /api/v1/admin/members/{member}/restore`

Behavior:

- Supports listing, detail, safe-field updates, status changes, soft delete, and restore.
- Uses policy checks for major operations.
- Summary endpoint exposes aggregates.

Observations:

- Member management is typical admin CRUD with workflow/status handling layered on top.
- Status changes can also affect linked user access.

## Module 6: Committee Management

Primary files:

- `app/Http/Controllers/AdminCommitteeTypeController.php`
- `app/Http/Controllers/AdminCommitteeController.php`
- `app/Services/CommitteeTypeService.php`
- `app/Services/CommitteeService.php`

Committee type endpoints:

- `GET /api/v1/admin/committee-types`
- `POST /api/v1/admin/committee-types`
- `GET /api/v1/admin/committee-types/{committeeType}`
- `PUT /api/v1/admin/committee-types/{committeeType}`
- `DELETE /api/v1/admin/committee-types/{committeeType}`
- `PUT /api/v1/admin/committee-types/{committeeType}/restore`

Committee endpoints:

- `GET /api/v1/admin/committees-summary`
- `GET /api/v1/admin/committees-tree`
- `GET /api/v1/admin/committees`
- `POST /api/v1/admin/committees`
- `GET /api/v1/admin/committees/{committee}`
- `PUT /api/v1/admin/committees/{committee}`
- `PATCH /api/v1/admin/committees/{committee}/status`
- `DELETE /api/v1/admin/committees/{committee}`
- `PUT /api/v1/admin/committees/{committee}/restore`

Behavior:

- Committee data supports both flat admin CRUD and tree-based retrieval.
- Soft-delete and restore are built in.
- Status changes are explicit.

Observations:

- The tree endpoint is a strong signal that committees are hierarchical, not just categorized records.

## Module 7: Positions and Assignments

Primary files:

- `app/Http/Controllers/AdminPositionController.php`
- `app/Http/Controllers/AdminCommitteeMemberAssignmentController.php`
- `app/Services/PositionService.php`
- `app/Services/CommitteeMemberAssignmentService.php`

Position endpoints:

- `GET /api/v1/admin/positions-summary`
- `GET /api/v1/admin/positions`
- `POST /api/v1/admin/positions`
- `GET /api/v1/admin/positions/{position}`
- `PUT /api/v1/admin/positions/{position}`
- `PATCH /api/v1/admin/positions/{position}/status`
- `DELETE /api/v1/admin/positions/{position}`
- `PUT /api/v1/admin/positions/{position}/restore`

Assignment endpoints:

- `GET /api/v1/admin/committee-member-assignments-summary`
- `GET /api/v1/admin/committee-member-assignments`
- `POST /api/v1/admin/committee-member-assignments`
- `GET /api/v1/admin/committee-member-assignments/{assignment}`
- `PUT /api/v1/admin/committee-member-assignments/{assignment}`
- `PATCH /api/v1/admin/committee-member-assignments/{assignment}/status`
- `POST /api/v1/admin/committee-member-assignments/{assignment}/transfer`
- `DELETE /api/v1/admin/committee-member-assignments/{assignment}`
- `PUT /api/v1/admin/committee-member-assignments/{assignment}/restore`
- `GET /api/v1/admin/committees/{committeeId}/members`
- `GET /api/v1/admin/committees/{committeeId}/office-bearers`
- `GET /api/v1/admin/members/{memberId}/committee-assignments`

Behavior:

- Assignment management goes beyond CRUD and includes transfer workflows and scoped lookup endpoints.
- There are dedicated history models for assignments and positions.

Observations:

- This is one of the richer modules and likely central to organizational operations.

## Module 8: Hierarchy and Reporting Relations

Primary files:

- `app/Http/Controllers/AdminMemberReportingRelationController.php`
- `app/Services/MemberReportingRelationService.php`

Endpoints:

- `GET /api/v1/admin/member-reporting-relations-summary`
- `GET /api/v1/admin/member-reporting-relations`
- `POST /api/v1/admin/member-reporting-relations`
- `GET /api/v1/admin/member-reporting-relations/{id}`
- `PUT /api/v1/admin/member-reporting-relations/{id}`
- `PATCH /api/v1/admin/member-reporting-relations/{id}/status`
- `DELETE /api/v1/admin/member-reporting-relations/{id}`
- `PUT /api/v1/admin/member-reporting-relations/{id}/restore`
- `GET /api/v1/admin/committee-member-assignments/{assignmentId}/leader`
- `GET /api/v1/admin/committee-member-assignments/{assignmentId}/subordinates`
- `GET /api/v1/admin/committees/{committeeId}/hierarchy-tree`

Behavior:

- Models direct reporting structures separately from committee assignment records.
- Exposes both record management and tree/query endpoints.

Observations:

- This module gives the API an organizational graph layer, not just relational CRUD.

## Module 9: Content Management

Primary files:

- `app/Http/Controllers/AdminPostCategoryController.php`
- `app/Http/Controllers/AdminPostController.php`
- `app/Http/Controllers/AdminNoticeController.php`

Post category endpoints:

- `GET /api/v1/admin/post-categories`
- `POST /api/v1/admin/post-categories`
- `GET /api/v1/admin/post-categories/{id}`
- `PUT /api/v1/admin/post-categories/{id}`
- `PATCH /api/v1/admin/post-categories/{id}/status`
- `DELETE /api/v1/admin/post-categories/{id}`
- `PUT /api/v1/admin/post-categories/{id}/restore`

Post endpoints:

- `GET /api/v1/admin/posts-summary`
- `GET /api/v1/admin/posts`
- `POST /api/v1/admin/posts`
- `GET /api/v1/admin/posts/{id}`
- `PUT /api/v1/admin/posts/{id}`
- `PATCH /api/v1/admin/posts/{id}/status`
- `PATCH /api/v1/admin/posts/{id}/feature`
- `POST /api/v1/admin/posts/{id}/publish`
- `POST /api/v1/admin/posts/{id}/unpublish`
- `POST /api/v1/admin/posts/{id}/archive`
- `DELETE /api/v1/admin/posts/{id}`
- `PUT /api/v1/admin/posts/{id}/restore`

Notice endpoints:

- `GET /api/v1/admin/notices-summary`
- `GET /api/v1/admin/notices`
- `POST /api/v1/admin/notices`
- `GET /api/v1/admin/notices/{id}`
- `PUT /api/v1/admin/notices/{id}`
- `PATCH /api/v1/admin/notices/{id}/status`
- `PATCH /api/v1/admin/notices/{id}/pin`
- `POST /api/v1/admin/notices/{id}/attachments`
- `DELETE /api/v1/admin/notices/{id}/attachments/{attachmentId}`
- `DELETE /api/v1/admin/notices/{id}`
- `PUT /api/v1/admin/notices/{id}/restore`

Behavior:

- Content supports drafting, featuring, publishing, unpublishing, archiving, and pinning.
- Notices include attachment management.

Observations:

- This module is stronger than a basic CMS because it includes editorial workflow states.

## Module 10: Profile Update Administration

Primary files:

- `app/Http/Controllers/AdminProfileUpdateRequestController.php`
- `app/Services/ProfileUpdateRequestService.php`

Endpoints:

- `GET /api/v1/admin/profile-update-requests`
- `GET /api/v1/admin/profile-update-requests/{id}`
- `PATCH /api/v1/admin/profile-update-requests/{id}/approve`
- `PATCH /api/v1/admin/profile-update-requests/{id}/reject`

Behavior:

- Admins can review and resolve profile update requests from members.

Observations:

- This module complements the `/me/profile-update-requests` workflow and keeps approval-sensitive changes auditable.

## Module 11: Roles, Permissions, Dashboard, and Reports

Primary files:

- `app/Http/Controllers/Api/V1/AdminRoleController.php`
- `app/Http/Controllers/Api/V1/AdminPermissionController.php`
- `app/Http/Controllers/Api/V1/AdminUserRoleController.php`
- `app/Http/Controllers/AdminMenuController.php`
- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/ReportController.php`

Role and permission endpoints:

- `GET /api/v1/admin/menu`
- `GET /api/v1/admin/roles`
- `POST /api/v1/admin/roles`
- `GET /api/v1/admin/roles/summary`
- `GET /api/v1/admin/roles/{role}`
- `PUT /api/v1/admin/roles/{role}`
- `PATCH /api/v1/admin/roles/{role}/permissions`
- `DELETE /api/v1/admin/roles/{role}`
- `GET /api/v1/admin/permissions`
- `GET /api/v1/admin/permissions/grouped`
- `GET /api/v1/admin/permissions/{permission}`
- `PATCH /api/v1/admin/users/{user}/roles`

Dashboard endpoints:

- `GET /api/v1/dashboard`
- `GET /api/v1/dashboard/superadmin`
- `GET /api/v1/dashboard/admin`
- `GET /api/v1/dashboard/member`
- `GET /api/v1/dashboard/stats`
- `GET /api/v1/dashboard/charts`
- `GET /api/v1/dashboard/recent-activities`
- `GET /api/v1/dashboard/pending-items`
- `GET /api/v1/dashboard/latest-content`

Report endpoints:

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

Behavior:

- Admin menu is role-aware.
- Roles and permissions are handled through Spatie Permission models and policies.
- Dashboard endpoints are segmented by audience and aggregation type.
- Reports are permission-gated individually.

Observations:

- This module turns the backend into a real admin platform, not only an operational API.

## Architecture Notes

Strengths:

- Controllers are mostly thin.
- Services hold business logic.
- Resource classes shape responses cleanly.
- Request classes centralize validation.
- Policies are registered centrally.
- Exceptions are normalized to JSON for API calls.

Patterns used:

- Soft delete + restore in many admin modules
- Summary endpoints for quick metrics
- Paginated list endpoints with `meta`
- Explicit status transition endpoints
- Separate public/member/admin route spaces

## Risks and Improvement Areas

1. Authorization style is mixed.

- Some controllers use policies with `$this->authorize(...)`.
- Others use direct permission checks like `auth()->user()->can(...)`.
- A more uniform approach would improve predictability.

2. Docs and comments have minor naming drift.

- Example: role summary route lives at `/admin/roles/summary`, while one docblock references `/admin/roles-summary`.

3. Test coverage is limited relative to API size.

- The codebase exposes `163` API routes.
- Only a small set of feature tests currently exists.

4. Test environment is not fully working in the current setup.

- Running `php artisan test` failed because the SQLite driver is missing for the in-memory test database.

5. Some status-heavy modules deserve stronger contract tests.

- Membership applications
- assignments and transfers
- reporting hierarchy
- content publishing workflow
- self-service profile update approvals

## Suggested Next Steps

1. Add route/module coverage tests for the major admin modules not currently covered.
2. Standardize authorization style across controllers.
3. Fix minor documentation/comment mismatches.
4. Make the test environment runnable by enabling the SQLite PDO driver or switching tests to a configured database.
5. Add API contract examples for key frontend-facing endpoints.

## Conclusion

The API is well-structured, ambitious, and organized around real operational domains: onboarding, membership, committees, hierarchy, publishing, and administration. The architecture is strong enough for further growth, but the main gap is confidence at scale: the route surface is large, while automated verification is still comparatively thin and currently blocked in this environment by missing SQLite support.
