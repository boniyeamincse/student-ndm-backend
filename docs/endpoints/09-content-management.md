# Module 09: Content Management

Admin-level endpoints for managing posts, categories, and notices.

## Post Categories
**Prefix:** `/api/v1/admin/post-categories`
**Middleware:** `auth:sanctum`

### List All Categories
- **URL:** `/`
- **Method:** `GET`
- **Controller:** `AdminPostCategoryController@index`

### Create New Category
- **URL:** `/`
- **Method:** `POST`
- **Controller:** `AdminPostCategoryController@store`

### View Category Details
- **URL:** `/{id}`
- **Method:** `GET`
- **Controller:** `AdminPostCategoryController@show`

### Update Category
- **URL:** `/{id}`
- **Method:** `PUT`
- **Controller:** `AdminPostCategoryController@update`

### Update Category Status
- **URL:** `/{id}/status`
- **Method:** `PATCH`
- **Controller:** `AdminPostCategoryController@updateStatus`

### Delete Category
- **URL:** `/{id}`
- **Method:** `DELETE`
- **Controller:** `AdminPostCategoryController@destroy`

### Restore Category
- **URL:** `/{id}/restore`
- **Method:** `PUT`
- **Controller:** `AdminPostCategoryController@restore`

## Posts (News/Blogs)
**Prefix:** `/api/v1/admin/posts`
**Middleware:** `auth:sanctum`

### Posts Summary
- **URL:** `/api/v1/admin/posts-summary`
- **Method:** `GET`
- **Controller:** `AdminPostController@summary`

### List All Posts
- **URL:** `/`
- **Method:** `GET`
- **Controller:** `AdminPostController@index`

### Create New Post
- **URL:** `/`
- **Method:** `POST`
- **Controller:** `AdminPostController@store`

### View Post Details
- **URL:** `/{id}`
- **Method:** `GET`
- **Controller:** `AdminPostController@show`

### Update Post
- **URL:** `/{id}`
- **Method:** `PUT`
- **Controller:** `AdminPostController@update`

### Update Post Status
- **URL:** `/{id}/status`
- **Method:** `PATCH`
- **Controller:** `AdminPostController@updateStatus`

### Update Featured Status
- **URL:** `/{id}/feature`
- **Method:** `PATCH`
- **Controller:** `AdminPostController@updateFeature`

### Publish Post
- **URL:** `/{id}/publish`
- **Method:** `POST`
- **Controller:** `AdminPostController@publish`

### Unpublish Post
- **URL:** `/{id}/unpublish`
- **Method:** `POST`
- **Controller:** `AdminPostController@unpublish`

### Archive Post
- **URL:** `/{id}/archive`
- **Method:** `POST`
- **Controller:** `AdminPostController@archive`

### Delete Post
- **URL:** `/{id}`
- **Method:** `DELETE`
- **Controller:** `AdminPostController@destroy`

### Restore Post
- **URL:** `/{id}/restore`
- **Method:** `PUT`
- **Controller:** `AdminPostController@restore`

## Notices
**Prefix:** `/api/v1/admin/notices`
**Middleware:** `auth:sanctum`

### Notices Summary
- **URL:** `/api/v1/admin/notices-summary`
- **Method:** `GET`
- **Controller:** `AdminNoticeController@summary`

### List All Notices
- **URL:** `/`
- **Method:** `GET`
- **Controller:** `AdminNoticeController@index`

### Create New Notice
- **URL:** `/`
- **Method:** `POST`
- **Controller:** `AdminNoticeController@store`

### View Notice Details
- **URL:** `/{id}`
- **Method:** `GET`
- **Controller:** `AdminNoticeController@show`

### Update Notice
- **URL:** `/{id}`
- **Method:** `PUT`
- **Controller:** `AdminNoticeController@update"

### Update Notice Status
- **URL:** `/{id}/status`
- **Method:** `PATCH`
- **Controller:** `AdminNoticeController@updateStatus`

### Pin/Unpin Notice
- **URL:** `/{id}/pin`
- **Method:** `PATCH`
- **Controller:** `AdminNoticeController@updatePin`

### Add Attachments to Notice
- **URL:** `/{id}/attachments`
- **Method:** `POST`
- **Controller:** `AdminNoticeController@addAttachments`

### Remove Attachment from Notice
- **URL:** `/{id}/attachments/{attachmentId}`
- **Method:** `DELETE`
- **Controller:** `AdminNoticeController@removeAttachment`

### Delete Notice
- **URL:** `/{id}`
- **Method:** `DELETE`
- **Controller:** `AdminNoticeController@destroy`

### Restore Notice
- **URL:** `/{id}/restore`
- **Method:** `PUT`
- **Controller:** `AdminNoticeController@restore`
