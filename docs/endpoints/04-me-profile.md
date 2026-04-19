# Module 04: Me (Member Self-Service)

Endpoints for the authenticated member to manage their profile and view personal assignments.

**Prefix:** `/api/v1/me`
**Middleware:** `auth:sanctum`

## Profile Management

### View My Profile
- **URL:** `/profile`
- **Method:** `GET`
- **Controller:** `MeProfileController@profile`

### Update My Profile
- **URL:** `/profile`
- **Method:** `PUT`
- **Controller:** `MeProfileController@updateProfile`

### Update Profile Photo
- **URL:** `/profile/photo`
- **Method:** `POST`
- **Controller:** `MeProfileController@updatePhoto`

## Account and Assignments

### Account Settings
- **URL:** `/account-settings`
- **Method:** `GET`
- **Controller:** `MeProfileController@accountSettings`

### Update Account Settings
- **URL:** `/account-settings`
- **Method:** `PUT`
- **Controller:** `MeProfileController@updateAccountSettings`

### Member Overview
- **URL:** `/member-overview`
- **Method:** `GET`
- **Controller:** `MeProfileController@memberOverview`

### My Committee Assignments
- **URL:** `/committee-assignments`
- **Method:** `GET`
- **Controller:** `MeProfileController@committeeAssignments`

## Hierarchy Context

### My Leader
- **URL:** `/leader`
- **Method:** `GET`
- **Controller:** `MeProfileController@leader`

### My Subordinates
- **URL:** `/subordinates`
- **Method:** `GET`
- **Controller:** `MeProfileController@subordinates`

### Profile Summary
- **URL:** `/profile-summary`
- **Method:** `GET`
- **Controller:** `MeProfileController@profileSummary`

## Profile Update Requests

### List All My Requests
- **URL:** `/profile-update-requests`
- **Method:** `GET`
- **Controller:** `MeProfileUpdateRequestController@index`

### Create New Request
- **URL:** `/profile-update-requests`
- **Method:** `POST`
- **Controller:** `MeProfileUpdateRequestController@store`

### View Request Details
- **URL:** `/profile-update-requests/{id}`
- **Method:** `GET`
- **Controller:** `MeProfileUpdateRequestController@show`

### Cancel Request
- **URL:** `/profile-update-requests/{id}/cancel`
- **Method:** `POST`
- **Controller:** `MeProfileUpdateRequestController@cancel`

---
## Member Notices
**Prefix:** `/api/v1/member`

### List Member Notices
- **URL:** `/notices`
- **Method:** `GET`
- **Controller:** `MemberNoticeController@index`

### View Member Notice
- **URL:** `/notices/{slug}`
- **Method:** `GET`
- **Controller:** `MemberNoticeController@show`
