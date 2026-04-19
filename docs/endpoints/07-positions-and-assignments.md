# Module 07: Positions and Assignments

Admin-level endpoints for managing positions (designations) and assigning members to committees.

## Positions (Designations)
**Prefix:** `/api/v1/admin/positions`
**Middleware:** `auth:sanctum`

### Positions Summary
- **URL:** `/api/v1/admin/positions-summary`
- **Method:** `GET`
- **Controller:** `AdminPositionController@summary`

### List All Positions
- **URL:** `/`
- **Method:** `GET`
- **Controller:** `AdminPositionController@index`

### Create New Position
- **URL:** `/`
- **Method:** `POST`
- **Controller:** `AdminPositionController@store`

### View Position Details
- **URL:** `/{position}`
- **Method:** `GET`
- **Controller:** `AdminPositionController@show`

### Update Position
- **URL:** `/{position}`
- **Method:** `PUT`
- **Controller:** `AdminPositionController@update`

### Update Position Status
- **URL:** `/{position}/status`
- **Method:** `PATCH`
- **Controller:** `AdminPositionController@updateStatus`

### Delete Position
- **URL:** `/{position}`
- **Method:** `DELETE`
- **Controller:** `AdminPositionController@destroy`

### Restore Position
- **URL:** `/{position}/restore`
- **Method:** `PUT`
- **Controller:** `AdminPositionController@restore`

---

## Committee Member Assignments
**Prefix:** `/api/v1/admin/committee-member-assignments`
**Middleware:** `auth:sanctum`

### Assignments Summary
- **URL:** `/api/v1/admin/committee-member-assignments-summary`
- **Method:** `GET`
- **Controller:** `AdminCommitteeMemberAssignmentController@summary`

### List All Assignments
- **URL:** `/`
- **Method:** `GET`
- **Controller:** `AdminCommitteeMemberAssignmentController@index`

### Create New Assignment
- **URL:** `/`
- **Method:** `POST`
- **Controller:** `AdminCommitteeMemberAssignmentController@store`

### View Assignment Details
- **URL:** `/{assignment}`
- **Method:** `GET`
- **Controller:** `AdminCommitteeMemberAssignmentController@show`

### Update Assignment
- **URL:** `/{assignment}`
- **Method:** `PUT`
- **Controller:** `AdminCommitteeMemberAssignmentController@update`

### Update Assignment Status
- **URL:** `/{assignment}/status`
- **Method:** `PATCH`
- **Controller:** `AdminCommitteeMemberAssignmentController@updateStatus`

### Transfer Assignment
- **URL:** `/{assignment}/transfer`
- **Method:** `POST`
- **Controller:** `AdminCommitteeMemberAssignmentController@transfer`

### Delete Assignment
- **URL:** `/{assignment}`
- **Method:** `DELETE`
- **Controller:** `AdminCommitteeMemberAssignmentController@destroy`

### Restore Assignment
- **URL:** `/{assignment}/restore`
- **Method:** `PUT`
- **Controller:** `AdminCommitteeMemberAssignmentController@restore`

### View Members of a Committee
- **URL:** `/api/v1/admin/committees/{committeeId}/members`
- **Method:** `GET`
- **Controller:** `AdminCommitteeMemberAssignmentController@committeeMembers`

### View Office Bearers of a Committee
- **URL:** `/api/v1/admin/committees/{committeeId}/office-bearers`
- **Method:** `GET`
- **Controller:** `AdminCommitteeMemberAssignmentController@committeeOfficeBearers`

### View Assignments of a Member
- **URL:** `/api/v1/admin/members/{memberId}/committee-assignments`
- **Method:** `GET`
- **Controller:** `AdminCommitteeMemberAssignmentController@memberAssignments`
