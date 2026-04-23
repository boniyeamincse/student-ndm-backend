# Module 01: Auth

Endpoints for authentication and user management.

**Prefix:** `/api/v1/auth`

## Public Endpoints
These endpoints are rate-limited (`throttle:10,1`).

### Register
- **URL:** `/register`
- **Method:** `POST`
- **Controller:** `AuthController@register`

### Login
- **URL:** `/login`
- **Method:** `POST`
- **Controller:** `AuthController@login`

### Forgot Password
- **URL:** `/forgot-password`
- **Method:** `POST`
- **Controller:** `AuthController@forgotPassword`

### Social Redirect
- **URL:** `/social/{provider}/redirect`
- **Method:** `GET`
- **Controller:** `AuthController@socialRedirect`

### Social Callback
- **URL:** `/social/{provider}/callback`
- **Method:** `GET`
- **Controller:** `AuthController@socialCallback`

### Reset Password
- **URL:** `/reset-password`
- **Method:** `POST`
- **Controller:** `AuthController@resetPassword`

## Protected Endpoints
Requires `auth:sanctum` middleware.

### Get My Info
- **URL:** `/me`
- **Method:** `GET`
- **Controller:** `AuthController@me`

### Logout
- **URL:** `/logout`
- **Method:** `POST`
- **Controller:** `AuthController@logout`
- **Note:** Requires a current Sanctum API token for the request; tokenless Sanctum-authenticated requests now return a validation error instead of crashing.

### Logout All Sessions
- **URL:** `/logout-all`
- **Method:** `POST`
- **Controller:** `AuthController@logoutAll`

### Change Password
- **URL:** `/change-password`
- **Method:** `PUT`
- **Controller:** `AuthController@changePassword`
