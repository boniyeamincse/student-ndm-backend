# Module 08: Hierarchy and Reporting

Admin-level endpoints for managing the reporting structure and hierarchy trees within committees.

**Prefix:** `/api/v1/admin/member-reporting-relations`
**Middleware:** `auth:sanctum`

## Summary Endpoints

### Reporting Relations Summary
- **URL:** `/api/v1/admin/member-reporting-relations-summary`
- **Method:** `GET`
- **Controller:** `AdminMemberReportingRelationController@summary`

## Relation Management

### List All Relations
- **URL:** `/`
- **Method:** `GET`
- **Controller:** `AdminMemberReportingRelationController@index`

### Create New Relation
- **URL:** `/`
- **Method:** `POST`
- **Controller:** `AdminMemberReportingRelationController@store`

### View Relation Details
- **URL:** `/{id}`
- **Method:** `GET`
- **Controller:** `AdminMemberReportingRelationController@show`

### Update Relation
- **URL:** `/{id}`
- **Method:** `PUT`
- **Controller:** `AdminMemberReportingRelationController@update`

### Update Relation Status
- **URL:** `/{id}/status`
- **Method:** `PATCH`
- **Controller:** `AdminMemberReportingRelationController@updateStatus`

### Delete Relation
- **URL:** `/{id}`
- **Method:** `DELETE`
- **Controller:** `AdminMemberReportingRelationController@destroy`

### Restore Relation
- **URL:** `/{id}/restore`
- **Method:** `PUT`
- **Controller:** `AdminMemberReportingRelationController@restore`

## Hierarchy Lookup Endpoints

### Get Leader of an Assignment
- **URL:** `/api/v1/admin/committee-member-assignments/{assignmentId}/leader`
- **Method:** `GET`
- **Controller:** `AdminMemberReportingRelationController@leader`

### Get Subordinates of an Assignment
- **URL:** `/api/v1/admin/committee-member-assignments/{assignmentId}/subordinates`
- **Method:** `GET`
- **Controller:** `AdminMemberReportingRelationController@subordinates`

### View Committee Hierarchy Tree
- **URL:** `/api/v1/admin/committees/{committeeId}/hierarchy-tree`
- **Method:** `GET`
- **Controller:** `AdminMemberReportingRelationController@hierarchyTree`
