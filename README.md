# NDM Backend API

Laravel-based REST API for NDM membership, committee operations, notices, posts, reporting, and dashboard modules.

## Base Information

- Base URL (local): `http://127.0.0.1:8001/api`
- API version prefix: `/v1`
- Full versioned base: `http://127.0.0.1:8001/api/v1`
- Auth mechanism: Laravel Sanctum bearer token (`auth:sanctum`)
- Default response format: JSON

## Quick Start

1. Install dependencies:

```bash
composer install
```

2. Prepare environment:

```bash
cp .env.example .env
php artisan key:generate
```

3. Ensure the `pdo_sqlite` PHP extension is installed and enabled (required for the test suite's in-memory SQLite database):

```bash
# Ubuntu / Debian
sudo apt-get install php-sqlite3

# Verify:
php -m | grep -i sqlite
```

4. Configure database in `.env`, then run:

```bash
php artisan migrate --seed
```

4. Start server:

```bash
php artisan serve --port=8001
```

5. Verify API health:

```bash
curl -X GET http://127.0.0.1:8001/api/health
```

## Authentication

### Login

`POST /api/v1/auth/login`

Example:

```bash
curl -X POST http://127.0.0.1:8001/api/v1/auth/login \
        -H "Accept: application/json" \
        -H "Content-Type: application/json" \
        -d '{
                "login": "admin@example.com",
                "password": "Password@123"
        }'
```

Use the returned token as:

```http
Authorization: Bearer <your_token>
```

### Public Auth Endpoints

- `POST /auth/register`
- `POST /auth/login`
- `POST /auth/forgot-password`
- `POST /auth/reset-password`
- `GET /auth/social/{provider}/redirect`
- `GET /auth/social/{provider}/callback`

### Protected Auth Endpoints

- `GET /auth/me`
- `POST /auth/logout`
- `POST /auth/logout-all`
- `PUT /auth/change-password`

## Rate Limits

- Auth public endpoints: `throttle:10,1`
- Membership apply endpoint: `throttle:5,1`

## API Modules (v1)

All routes below are prefixed with `/api/v1`.

### System

- `GET /api/health`

### Public Membership

- `POST /membership/apply`

### Public Content

- Posts:
	- `GET /public/posts`
	- `GET /public/posts/{slug}`
	- `GET /public/post-categories`
	- `GET /public/featured-posts`
	- `GET /public/news`
	- `GET /public/blogs`
- Notices:
	- `GET /public/notices`
	- `GET /public/notices/{slug}`
	- `GET /public/pinned-notices`

### Member (Authenticated)

- Notices:
	- `GET /member/notices`
	- `GET /member/notices/{slug}`
- Self profile and account:
	- `GET /me/profile`
	- `PUT /me/profile`
	- `POST /me/profile/photo`
	- `GET /me/account-settings`
	- `PUT /me/account-settings`
	- `GET /me/member-overview`
	- `GET /me/committee-assignments`
	- `GET /me/leader`
	- `GET /me/subordinates`
	- `GET /me/profile-summary`
- Profile update requests:
	- `GET /me/profile-update-requests`
	- `POST /me/profile-update-requests`
	- `GET /me/profile-update-requests/{id}`
	- `POST /me/profile-update-requests/{id}/cancel`

### Admin (Authenticated)

- Menu:
	- `GET /admin/menu`
- Membership applications:
	- `GET /admin/membership-applications`
	- `GET /admin/membership-applications/{id}`
	- `PUT /admin/membership-applications/{id}/review`
	- `PUT /admin/membership-applications/{id}/approve`
	- `PUT /admin/membership-applications/{id}/reject`
	- `PUT /admin/membership-applications/{id}/hold`
	- `DELETE /admin/membership-applications/{id}`
	- `PUT /admin/membership-applications/{id}/restore`
- Members:
	- `GET /admin/members-summary`
	- `GET /admin/members`
	- `GET /admin/members/{member}`
	- `PUT /admin/members/{member}`
	- `PATCH /admin/members/{member}/status`
	- `DELETE /admin/members/{member}`
	- `PUT /admin/members/{member}/restore`
- Committee types:
	- `GET /admin/committee-types`
	- `POST /admin/committee-types`
	- `GET /admin/committee-types/{committeeType}`
	- `PUT /admin/committee-types/{committeeType}`
	- `DELETE /admin/committee-types/{committeeType}`
	- `PUT /admin/committee-types/{committeeType}/restore`
- Committees:
	- `GET /admin/committees-summary`
	- `GET /admin/committees-tree`
	- `GET /admin/committees`
	- `POST /admin/committees`
	- `GET /admin/committees/{committee}`
	- `PUT /admin/committees/{committee}`
	- `PATCH /admin/committees/{committee}/status`
	- `DELETE /admin/committees/{committee}`
	- `PUT /admin/committees/{committee}/restore`
- Positions:
	- `GET /admin/positions-summary`
	- `GET /admin/positions`
	- `POST /admin/positions`
	- `GET /admin/positions/{position}`
	- `PUT /admin/positions/{position}`
	- `PATCH /admin/positions/{position}/status`
	- `DELETE /admin/positions/{position}`
	- `PUT /admin/positions/{position}/restore`
- Committee member assignments:
	- `GET /admin/committee-member-assignments-summary`
	- `GET /admin/committee-member-assignments`
	- `POST /admin/committee-member-assignments`
	- `GET /admin/committee-member-assignments/{assignment}`
	- `PUT /admin/committee-member-assignments/{assignment}`
	- `PATCH /admin/committee-member-assignments/{assignment}/status`
	- `POST /admin/committee-member-assignments/{assignment}/transfer`
	- `DELETE /admin/committee-member-assignments/{assignment}`
	- `PUT /admin/committee-member-assignments/{assignment}/restore`
	- `GET /admin/committees/{committeeId}/members`
	- `GET /admin/committees/{committeeId}/office-bearers`
	- `GET /admin/members/{memberId}/committee-assignments`
- Reporting relations:
	- `GET /admin/member-reporting-relations-summary`
	- `GET /admin/member-reporting-relations`
	- `POST /admin/member-reporting-relations`
	- `GET /admin/member-reporting-relations/{id}`
	- `PUT /admin/member-reporting-relations/{id}`
	- `PATCH /admin/member-reporting-relations/{id}/status`
	- `DELETE /admin/member-reporting-relations/{id}`
	- `PUT /admin/member-reporting-relations/{id}/restore`
	- `GET /admin/committee-member-assignments/{assignmentId}/leader`
	- `GET /admin/committee-member-assignments/{assignmentId}/subordinates`
	- `GET /admin/committees/{committeeId}/hierarchy-tree`
- Post categories and posts:
	- `GET /admin/posts-summary`
	- `GET /admin/post-categories`
	- `POST /admin/post-categories`
	- `GET /admin/post-categories/{id}`
	- `PUT /admin/post-categories/{id}`
	- `PATCH /admin/post-categories/{id}/status`
	- `DELETE /admin/post-categories/{id}`
	- `PUT /admin/post-categories/{id}/restore`
	- `GET /admin/posts`
	- `POST /admin/posts`
	- `GET /admin/posts/{id}`
	- `PUT /admin/posts/{id}`
	- `PATCH /admin/posts/{id}/status`
	- `PATCH /admin/posts/{id}/feature`
	- `POST /admin/posts/{id}/publish`
	- `POST /admin/posts/{id}/unpublish`
	- `POST /admin/posts/{id}/archive`
	- `DELETE /admin/posts/{id}`
	- `PUT /admin/posts/{id}/restore`
- Notices:
	- `GET /admin/notices-summary`
	- `GET /admin/notices`
	- `POST /admin/notices`
	- `GET /admin/notices/{id}`
	- `PUT /admin/notices/{id}`
	- `PATCH /admin/notices/{id}/status`
	- `PATCH /admin/notices/{id}/pin`
	- `POST /admin/notices/{id}/attachments`
	- `DELETE /admin/notices/{id}/attachments/{attachmentId}`
	- `DELETE /admin/notices/{id}`
	- `PUT /admin/notices/{id}/restore`
- Admin profile update requests:
	- `GET /admin/profile-update-requests`
	- `GET /admin/profile-update-requests/{id}`
	- `PATCH /admin/profile-update-requests/{id}/approve`
	- `PATCH /admin/profile-update-requests/{id}/reject`
- Roles (superadmin only):
	- `GET /admin/roles`
	- `POST /admin/roles`
	- `GET /admin/roles/summary`
	- `GET /admin/roles/{role}`
	- `PUT /admin/roles/{role}`
	- `PATCH /admin/roles/{role}/permissions`
	- `DELETE /admin/roles/{role}`
- Permissions (superadmin only):
	- `GET /admin/permissions`
	- `GET /admin/permissions/grouped`
	- `GET /admin/permissions/{permission}`
- User management (superadmin only):
	- `GET /admin/users/lookup`
	- `PATCH /admin/users/{user}/roles`

### Dashboard and Reports (Authenticated)

- Dashboard:
	- `GET /dashboard`
	- `GET /dashboard/superadmin`
	- `GET /dashboard/admin`
	- `GET /dashboard/member`
	- `GET /dashboard/stats`
	- `GET /dashboard/charts`
	- `GET /dashboard/recent-activities`
	- `GET /dashboard/pending-items`
	- `GET /dashboard/latest-content`
- Reports:
	- `GET /reports/overview`
	- `GET /reports/membership-applications`
	- `GET /reports/members`
	- `GET /reports/committees`
	- `GET /reports/committee-assignments`
	- `GET /reports/reporting-hierarchy`
	- `GET /reports/posts`
	- `GET /reports/notices`
	- `GET /reports/profile-update-requests`
	- `GET /reports/activity-summary`

## Useful Commands

List API routes:

```bash
php artisan route:list --path=api
```

Run tests:

```bash
php artisan test
```

## Notes

- Route and module definitions are maintained in `routes/api.php` and `routes/api_v1.php`.
- If route changes are made, update this README to keep endpoint documentation in sync.
