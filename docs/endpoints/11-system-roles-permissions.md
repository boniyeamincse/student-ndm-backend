# Module 11: System Roles & Permissions

Admin-level endpoints for system-wide roles, permissions, and user assignments.

**Middleware:** `auth:sanctum`

## Roles Management
**Prefix:** `/api/v1/admin/roles`

### List All Roles
- **URL:** `/`
- **Method:** `GET`
- **Controller:** `AdminRoleController@index`

### Create New Role
- **URL:** `/`
- **Method:** `POST`
- **Controller:** `AdminRoleController@store`

### Get Roles Summary
- **URL:** `/summary`
- **Method:** `GET`
- **Controller:** `AdminRoleController@summary`

### View Role Details
- **URL:** `/{role}`
- **Method:** `GET`
- **Controller:** `AdminRoleController@show`

### Update Role
- **URL:** `/{role}`
- **Method:** `PUT`
- **Controller:** `AdminRoleController@update`

### Sync Permissions for Role
- **URL:** `/{role}/permissions`
- **Method:** `PATCH`
- **Controller:** `AdminRoleController@syncPermissions`

### Delete Role
- **URL:** `/{role}`
- **Method:** `DELETE`
- **Controller:** `AdminRoleController@destroy`

---

## Permissions Management
**Prefix:** `/api/v1/admin/permissions`

### List All Permissions
- **URL:** `/`
- **Method:** `GET`
- **Controller:** `AdminPermissionController@index`

### List Grouped Permissions
- **URL:** `/grouped`
- **Method:** `GET`
- **Controller:** `AdminPermissionController@grouped`

### View Permission Details
- **URL:** `/{permission}`
- **Method:** `GET`
- **Controller:** `AdminPermissionController@show`

---

## User Roles Assignment

### Sync Roles for User
- **URL:** `/api/v1/admin/users/{user}/roles`
- **Method:** `PATCH`
- **Controller:** `AdminUserRoleController@syncRoles`
