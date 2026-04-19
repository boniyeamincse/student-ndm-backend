# Module 05: Member Management

Admin-level endpoints for managing members within the system.

**Prefix:** `/api/v1/admin/members`
**Middleware:** `auth:sanctum`

## Summary Endpoints

### Members Summary
- **URL:** `/api/v1/admin/members-summary`
- **Method:** `GET`
- **Controller:** `AdminMemberController@summary`

## Member CRUD

### List All Members
- **URL:** `/`
- **Method:** `GET`
- **Controller:** `AdminMemberController@index`

### View Member Details
- **URL:** `/{member}`
- **Method:** `GET`
- **Controller:** `AdminMemberController@show`

### Update Member
- **URL:** `/{member}`
- **Method:** `PUT`
- **Controller:** `AdminMemberController@update`

### Update Member Status
- **URL:** `/{member}/status`
- **Method:** `PATCH`
- **Controller:** `AdminMemberController@updateStatus`

### Delete Member
- **URL:** `/{member}`
- **Method:** `DELETE`
- **Controller:** `AdminMemberController@destroy`

### Restore Member
- **URL:** `/{member}/restore`
- **Method:** `PUT`
- **Controller:** `AdminMemberController@restore`

---

## Admin Menu
- **URL:** `/api/v1/admin/menu`
- **Method:** `GET`
- **Controller:** `AdminMenuController@index`

---

## Profile Update Requests
**Prefix:** `/api/v1/admin/profile-update-requests`

### List All Requests
- **URL:** `/`
- **Method:** `GET`
- **Controller:** `AdminProfileUpdateRequestController@index`

### View Request Details
- **URL:** `/{id}`
- **Method:** `GET`
- **Controller:** `AdminProfileUpdateRequestController@show`

### Approve Request
- **URL:** `/{id}/approve`
- **Method:** `PATCH`
- **Controller:** `AdminProfileUpdateRequestController@approve`

### Reject Request
- **URL:** `/{id}/reject`
- **Method:** `PATCH`
- **Controller:** `AdminProfileUpdateRequestController@reject`
