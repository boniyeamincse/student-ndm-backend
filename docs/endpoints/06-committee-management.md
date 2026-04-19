# Module 06: Committee Management

Admin-level endpoints for managing committee types and committees.

## Committee Types
**Prefix:** `/api/v1/admin/committee-types`
**Middleware:** `auth:sanctum`

### List Committee Types
- **URL:** `/`
- **Method:** `GET`
- **Controller:** `AdminCommitteeTypeController@index`

### Create Committee Type
- **URL:** `/`
- **Method:** `POST`
- **Controller:** `AdminCommitteeTypeController@store`

### View Committee Type
- **URL:** `/{committeeType}`
- **Method:** `GET`
- **Controller:** `AdminCommitteeTypeController@show`

### Update Committee Type
- **URL:** `/{committeeType}`
- **Method:** `PUT`
- **Controller:** `AdminCommitteeTypeController@update`

### Delete Committee Type
- **URL:** `/{committeeType}`
- **Method:** `DELETE`
- **Controller:** `AdminCommitteeTypeController@destroy`

### Restore Committee Type
- **URL:** `/{committeeType}/restore`
- **Method:** `PUT`
- **Controller:** `AdminCommitteeTypeController@restore`

## Committees
**Prefix:** `/api/v1/admin/committees`
**Middleware:** `auth:sanctum`

### Committees Summary
- **URL:** `/api/v1/admin/committees-summary`
- **Method:** `GET`
- **Controller:** `AdminCommitteeController@summary`

### Committees Tree Representation
- **URL:** `/api/v1/admin/committees-tree`
- **Method:** `GET`
- **Controller:** `AdminCommitteeController@tree`

### List All Committees
- **URL:** `/`
- **Method:** `GET`
- **Controller:** `AdminCommitteeController@index`

### Create Committee
- **URL:** `/`
- **Method:** `POST`
- **Controller:** `AdminCommitteeController@store`

### View Committee Details
- **URL:** `/{committee}`
- **Method:** `GET`
- **Controller:** `AdminCommitteeController@show`

### Update Committee
- **URL:** `/{committee}`
- **Method:** `PUT`
- **Controller:** `AdminCommitteeController@update`

### Update Committee Status
- **URL:** `/{committee}/status`
- **Method:** `PATCH`
- **Controller:** `AdminCommitteeController@updateStatus`

### Delete Committee
- **URL:** `/{committee}`
- **Method:** `DELETE`
- **Controller:** `AdminCommitteeController@destroy`

### Restore Committee
- **URL:** `/{committee}/restore`
- **Method:** `PUT`
- **Controller:** `AdminCommitteeController@restore`
