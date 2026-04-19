# Module 03: Public Content

Endpoints for publicly accessible posts, news, and notices.

**Prefix:** `/api/v1/public`

## Posts and News

### List All Posts
- **URL:** `/posts`
- **Method:** `GET`
- **Controller:** `PublicPostController@index`

### View Post Details
- **URL:** `/posts/{slug}`
- **Method:** `GET`
- **Controller:** `PublicPostController@show`

### List Post Categories
- **URL:** `/post-categories`
- **Method:** `GET`
- **Controller:** `PublicPostController@categories`

### Featured Posts
- **URL:** `/featured-posts`
- **Method:** `GET`
- **Controller:** `PublicPostController@featured`

### News Feed
- **URL:** `/news`
- **Method:** `GET`
- **Controller:** `PublicPostController@news`

### Blog Feed
- **URL:** `/blogs`
- **Method:** `GET`
- **Controller:** `PublicPostController@blogs`

## Public Notices

### List All Notices
- **URL:** `/notices`
- **Method:** `GET`
- **Controller:** `PublicNoticeController@index`

### View Notice Details
- **URL:** `/notices/{slug}`
- **Method:** `GET`
- **Controller:** `PublicNoticeController@show`

### Pinned Notices
- **URL:** `/pinned-notices`
- **Method:** `GET`
- **Controller:** `PublicNoticeController@pinned`
