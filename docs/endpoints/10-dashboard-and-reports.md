# Module 10: Dashboard and Reports

Endpoints for dashboard statistics, activities, and generated reports.

**Middleware:** `auth:sanctum`

## Dashboard Endpoints
**Prefix:** `/api/v1/dashboard`

### Dashboard Overview
- **URL:** `/`
- **Method:** `GET`
- **Controller:** `DashboardController@index`

### Superadmin Specific Stats
- **URL:** `/superadmin`
- **Method:** `GET`
- **Controller:** `DashboardController@superadmin`

### Admin Specific Stats
- **URL:** `/admin`
- **Method:** `GET`
- **Controller:** `DashboardController@admin`

### Member Specific Stats
- **URL:** `/member`
- **Method:** `GET`
- **Controller:** `DashboardController@member`

### General Stats
- **URL:** `/stats`
- **Method:** `GET`
- **Controller:** `DashboardController@stats`

### Chart Data
- **URL:** `/charts`
- **Method:** `GET`
- **Controller:** `DashboardController@charts`

### Recent Activities
- **URL:** `/recent-activities`
- **Method:** `GET`
- **Controller:** `DashboardController@recentActivities`

### Pending Items
- **URL:** `/pending-items`
- **Method:** `GET`
- **Controller:** `DashboardController@pendingItems`

### Latest Content
- **URL:** `/latest-content`
- **Method:** `GET`
- **Controller:** `DashboardController@latestContent`

---

## Reports Endpoints
**Prefix:** `/api/v1/reports`

### Reports Overview
- **URL:** `/overview`
- **Method:** `GET`
- **Controller:** `ReportController@overview`

### Membership Applications Report
- **URL:** `/membership-applications`
- **Method:** `GET`
- **Controller:** `ReportController@membershipApplications`

### Members Report
- **URL:** `/members`
- **Method:** `GET`
- **Controller:** `ReportController@members`

### Committees Report
- **URL:** `/committees`
- **Method:** `GET`
- **Controller:** `ReportController@committees`

### Committee Assignments Report
- **URL:** `/committee-assignments`
- **Method:** `GET`
- **Controller:** `ReportController@committeeAssignments`

### Reporting Hierarchy Report
- **URL:** `/reporting-hierarchy`
- **Method:** `GET`
- **Controller:** `ReportController@reportingHierarchy`

### Posts Report
- **URL:** `/posts`
- **Method:** `GET`
- **Controller:** `ReportController@posts`

### Notices Report
- **URL:** `/notices`
- **Method:** `GET`
- **Controller:** `ReportController@notices`

### Profile Update Requests Report
- **URL:** `/profile-update-requests`
- **Method:** `GET`
- **Controller:** `ReportController@profileUpdateRequests`

### Activity Summary
- **URL:** `/activity-summary`
- **Method:** `GET`
- **Controller:** `ReportController@activitySummary`
