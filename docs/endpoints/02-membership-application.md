# Module 02: Membership Application

Endpoints for public membership applications and administrative management.

## Public Application
**Prefix:** `/api/v1/membership`

### Apply for Membership
- **URL:** `/apply`
- **Method:** `POST`
- **Controller:** `MembershipApplicationController@apply`
- **Middleware:** `throttle:5,1`

## Admin Management
**Prefix:** `/api/v1/admin/membership-applications`
**Middleware:** `auth:sanctum`

### List All Applications
- **URL:** `/`
- **Method:** `GET`
- **Controller:** `AdminMembershipApplicationController@index`

### View Application Details
- **URL:** `/{id}`
- **Method:** `GET`
- **Controller:** `AdminMembershipApplicationController@show`

### Review Application
- **URL:** `/{id}/review`
- **Method:** `PUT`
- **Controller:** `AdminMembershipApplicationController@review`

### Approve Application
- **URL:** `/{id}/approve`
- **Method:** `PUT`
- **Controller:** `AdminMembershipApplicationController@approve`

### Reject Application
- **URL:** `/{id}/reject`
- **Method:** `PUT`
- **Controller:** `AdminMembershipApplicationController@reject`

### Put Application on Hold
- **URL:** `/{id}/hold`
- **Method:** `PUT`
- **Controller:** `AdminMembershipApplicationController@hold`

### Delete Application
- **URL:** `/{id}`
- **Method:** `DELETE`
- **Controller:** `AdminMembershipApplicationController@destroy`

### Restore Deleted Application
- **URL:** `/{id}/restore`
- **Method:** `PUT`
- **Controller:** `AdminMembershipApplicationController@restore`
