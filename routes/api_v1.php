<?php

use App\Http\Controllers\AdminMemberController;
use App\Http\Controllers\AdminCommitteeController;
use App\Http\Controllers\AdminCommitteeMemberAssignmentController;
use App\Http\Controllers\AdminCommitteeTypeController;
use App\Http\Controllers\AdminMemberReportingRelationController;
use App\Http\Controllers\AdminPostCategoryController;
use App\Http\Controllers\AdminPostController;
use App\Http\Controllers\AdminPositionController;
use App\Http\Controllers\AdminNoticeController;
use App\Http\Controllers\AdminProfileUpdateRequestController;
use App\Http\Controllers\AdminMenuController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberNoticeController;
use App\Http\Controllers\MeProfileController;
use App\Http\Controllers\MeProfileUpdateRequestController;
use App\Http\Controllers\PublicPostController;
use App\Http\Controllers\PublicNoticeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Api\V1\AdminMembershipApplicationController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MembershipApplicationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
| Prefix:  /api/v1
| Auth:     Laravel Sanctum token (auth:sanctum middleware)
*/

Route::prefix('v1')->group(function () {

    // ── Auth ───────────────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {

        // Public — rate-limited
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/register',        [AuthController::class, 'register']);
            Route::post('/login',           [AuthController::class, 'login']);
            Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        });

        Route::get('/social/{provider}/redirect', [AuthController::class, 'socialRedirect']);
        Route::get('/social/{provider}/callback', [AuthController::class, 'socialCallback']);

        Route::post('/reset-password', [AuthController::class, 'resetPassword']);

        // Protected
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me',              [AuthController::class, 'me']);
            Route::post('/logout',         [AuthController::class, 'logout']);
            Route::post('/logout-all',     [AuthController::class, 'logoutAll']);
            Route::put('/change-password', [AuthController::class, 'changePassword']);
        });
    });

    // ── Module 02: Public Membership Application ───────────────────────────
    Route::prefix('membership')->group(function () {
        // throttle: 5 submissions per minute per IP to prevent spam
        Route::middleware('throttle:5,1')->group(function () {
            Route::post('/apply', [MembershipApplicationController::class, 'apply']);
        });
    });

    // ── Module 08: Public Posts / News ───────────────────────────────────
    Route::prefix('public')->group(function () {
        Route::get('/posts', [PublicPostController::class, 'index']);
        Route::get('/posts/{slug}', [PublicPostController::class, 'show']);
        Route::get('/post-categories', [PublicPostController::class, 'categories']);
        Route::get('/featured-posts', [PublicPostController::class, 'featured']);
        Route::get('/news', [PublicPostController::class, 'news']);
        Route::get('/blogs', [PublicPostController::class, 'blogs']);

        // ── Module 09: Public Notices ───────────────────────────────────
        Route::get('/notices', [PublicNoticeController::class, 'index']);
        Route::get('/notices/{slug}', [PublicNoticeController::class, 'show']);
        Route::get('/pinned-notices', [PublicNoticeController::class, 'pinned']);
    });

    // ── Module 09: Member Notices ─────────────────────────────────────────
    Route::prefix('member')->middleware('auth:sanctum')->group(function () {
        Route::get('/notices', [MemberNoticeController::class, 'index']);
        Route::get('/notices/{slug}', [MemberNoticeController::class, 'show']);
    });

    // ── Module 10: Profile Self-Service & Member Account Management ─────
    Route::prefix('me')->middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [MeProfileController::class, 'profile']);
        Route::put('/profile', [MeProfileController::class, 'updateProfile']);
        Route::post('/profile/photo', [MeProfileController::class, 'updatePhoto']);

        Route::get('/account-settings', [MeProfileController::class, 'accountSettings']);
        Route::put('/account-settings', [MeProfileController::class, 'updateAccountSettings']);

        Route::get('/member-overview', [MeProfileController::class, 'memberOverview']);
        Route::get('/committee-assignments', [MeProfileController::class, 'committeeAssignments']);
        Route::get('/leader', [MeProfileController::class, 'leader']);
        Route::get('/subordinates', [MeProfileController::class, 'subordinates']);
        Route::get('/profile-summary', [MeProfileController::class, 'profileSummary']);

        Route::prefix('profile-update-requests')->group(function () {
            Route::get('/', [MeProfileUpdateRequestController::class, 'index']);
            Route::post('/', [MeProfileUpdateRequestController::class, 'store']);
            Route::get('/{id}', [MeProfileUpdateRequestController::class, 'show']);
            Route::post('/{id}/cancel', [MeProfileUpdateRequestController::class, 'cancel']);
        });
    });

    // ── Module 02: Admin Membership Application Management ─────────────────
    Route::prefix('admin')->middleware('auth:sanctum')->group(function () {

        Route::get('/menu', [AdminMenuController::class, 'index']);

        Route::prefix('membership-applications')->group(function () {
            Route::get('/',                          [AdminMembershipApplicationController::class, 'index']);
            Route::get('/{id}',                      [AdminMembershipApplicationController::class, 'show']);
            Route::put('/{id}/review',               [AdminMembershipApplicationController::class, 'review']);
            Route::put('/{id}/approve',              [AdminMembershipApplicationController::class, 'approve']);
            Route::put('/{id}/reject',               [AdminMembershipApplicationController::class, 'reject']);
            Route::put('/{id}/hold',                 [AdminMembershipApplicationController::class, 'hold']);
            Route::delete('/{id}',                   [AdminMembershipApplicationController::class, 'destroy']);
            Route::put('/{id}/restore',              [AdminMembershipApplicationController::class, 'restore']);
        });

        // ── Module 03: Member Management ──────────────────────────────────
        Route::get('/members-summary',               [AdminMemberController::class, 'summary']);

        Route::prefix('members')->group(function () {
            Route::get('/',                          [AdminMemberController::class, 'index']);
            Route::get('/{member}',                  [AdminMemberController::class, 'show']);
            Route::put('/{member}',                  [AdminMemberController::class, 'update']);
            Route::patch('/{member}/status',         [AdminMemberController::class, 'updateStatus']);
            Route::delete('/{member}',               [AdminMemberController::class, 'destroy']);
            Route::put('/{member}/restore',          [AdminMemberController::class, 'restore']);
        });

        // ── Module 04: Committee Type Management ──────────────────────────
        Route::prefix('committee-types')->group(function () {
            Route::get('/',                          [AdminCommitteeTypeController::class, 'index']);
            Route::post('/',                         [AdminCommitteeTypeController::class, 'store']);
            Route::get('/{committeeType}',           [AdminCommitteeTypeController::class, 'show']);
            Route::put('/{committeeType}',           [AdminCommitteeTypeController::class, 'update']);
            Route::delete('/{committeeType}',        [AdminCommitteeTypeController::class, 'destroy']);
            Route::put('/{committeeType}/restore',   [AdminCommitteeTypeController::class, 'restore']);
        });

        // ── Module 04: Committee Management ───────────────────────────────
        Route::get('/committees-summary',            [AdminCommitteeController::class, 'summary']);
        Route::get('/committees-tree',               [AdminCommitteeController::class, 'tree']);

        Route::prefix('committees')->group(function () {
            Route::get('/',                          [AdminCommitteeController::class, 'index']);
            Route::post('/',                         [AdminCommitteeController::class, 'store']);
            Route::get('/{committee}',               [AdminCommitteeController::class, 'show']);
            Route::put('/{committee}',               [AdminCommitteeController::class, 'update']);
            Route::patch('/{committee}/status',      [AdminCommitteeController::class, 'updateStatus']);
            Route::delete('/{committee}',            [AdminCommitteeController::class, 'destroy']);
            Route::put('/{committee}/restore',       [AdminCommitteeController::class, 'restore']);
        });

        // ── Module 05: Position / Designation Management ─────────────────
        Route::get('/positions-summary',             [AdminPositionController::class, 'summary']);

        Route::prefix('positions')->group(function () {
            Route::get('/',                          [AdminPositionController::class, 'index']);
            Route::post('/',                         [AdminPositionController::class, 'store']);
            Route::get('/{position}',                [AdminPositionController::class, 'show']);
            Route::put('/{position}',                [AdminPositionController::class, 'update']);
            Route::patch('/{position}/status',       [AdminPositionController::class, 'updateStatus']);
            Route::delete('/{position}',             [AdminPositionController::class, 'destroy']);
            Route::put('/{position}/restore',        [AdminPositionController::class, 'restore']);
        });

        // ── Module 06: Committee Member Assignment ───────────────────────
        Route::get('/committee-member-assignments-summary', [AdminCommitteeMemberAssignmentController::class, 'summary']);

        Route::prefix('committee-member-assignments')->group(function () {
            Route::get('/',                          [AdminCommitteeMemberAssignmentController::class, 'index']);
            Route::post('/',                         [AdminCommitteeMemberAssignmentController::class, 'store']);
            Route::get('/{assignment}',              [AdminCommitteeMemberAssignmentController::class, 'show']);
            Route::put('/{assignment}',              [AdminCommitteeMemberAssignmentController::class, 'update']);
            Route::patch('/{assignment}/status',     [AdminCommitteeMemberAssignmentController::class, 'updateStatus']);
            Route::post('/{assignment}/transfer',    [AdminCommitteeMemberAssignmentController::class, 'transfer']);
            Route::delete('/{assignment}',           [AdminCommitteeMemberAssignmentController::class, 'destroy']);
            Route::put('/{assignment}/restore',      [AdminCommitteeMemberAssignmentController::class, 'restore']);
        });

        Route::get('/committees/{committeeId}/members', [AdminCommitteeMemberAssignmentController::class, 'committeeMembers']);
        Route::get('/committees/{committeeId}/office-bearers', [AdminCommitteeMemberAssignmentController::class, 'committeeOfficeBearers']);
        Route::get('/members/{memberId}/committee-assignments', [AdminCommitteeMemberAssignmentController::class, 'memberAssignments']);

        // ── Module 07: Member Hierarchy / Reporting Structure ───────────
        Route::get('/member-reporting-relations-summary', [AdminMemberReportingRelationController::class, 'summary']);

        Route::prefix('member-reporting-relations')->group(function () {
            Route::get('/',                               [AdminMemberReportingRelationController::class, 'index']);
            Route::post('/',                              [AdminMemberReportingRelationController::class, 'store']);
            Route::get('/{id}',                           [AdminMemberReportingRelationController::class, 'show']);
            Route::put('/{id}',                           [AdminMemberReportingRelationController::class, 'update']);
            Route::patch('/{id}/status',                  [AdminMemberReportingRelationController::class, 'updateStatus']);
            Route::delete('/{id}',                        [AdminMemberReportingRelationController::class, 'destroy']);
            Route::put('/{id}/restore',                   [AdminMemberReportingRelationController::class, 'restore']);
        });

        Route::get('/committee-member-assignments/{assignmentId}/leader', [AdminMemberReportingRelationController::class, 'leader']);
        Route::get('/committee-member-assignments/{assignmentId}/subordinates', [AdminMemberReportingRelationController::class, 'subordinates']);
        Route::get('/committees/{committeeId}/hierarchy-tree', [AdminMemberReportingRelationController::class, 'hierarchyTree']);

        // ── Module 08: Blog / News Management ───────────────────────────
        Route::get('/posts-summary', [AdminPostController::class, 'summary']);

        Route::prefix('post-categories')->group(function () {
            Route::get('/', [AdminPostCategoryController::class, 'index']);
            Route::post('/', [AdminPostCategoryController::class, 'store']);
            Route::get('/{id}', [AdminPostCategoryController::class, 'show']);
            Route::put('/{id}', [AdminPostCategoryController::class, 'update']);
            Route::patch('/{id}/status', [AdminPostCategoryController::class, 'updateStatus']);
            Route::delete('/{id}', [AdminPostCategoryController::class, 'destroy']);
            Route::put('/{id}/restore', [AdminPostCategoryController::class, 'restore']);
        });

        Route::prefix('posts')->group(function () {
            Route::get('/', [AdminPostController::class, 'index']);
            Route::post('/', [AdminPostController::class, 'store']);
            Route::get('/{id}', [AdminPostController::class, 'show']);
            Route::put('/{id}', [AdminPostController::class, 'update']);
            Route::patch('/{id}/status', [AdminPostController::class, 'updateStatus']);
            Route::patch('/{id}/feature', [AdminPostController::class, 'updateFeature']);
            Route::post('/{id}/publish', [AdminPostController::class, 'publish']);
            Route::post('/{id}/unpublish', [AdminPostController::class, 'unpublish']);
            Route::post('/{id}/archive', [AdminPostController::class, 'archive']);
            Route::delete('/{id}', [AdminPostController::class, 'destroy']);
            Route::put('/{id}/restore', [AdminPostController::class, 'restore']);
        });

        // ── Module 09: Notice Management ───────────────────────────────
        Route::get('/notices-summary', [AdminNoticeController::class, 'summary']);

        Route::prefix('notices')->group(function () {
            Route::get('/', [AdminNoticeController::class, 'index']);
            Route::post('/', [AdminNoticeController::class, 'store']);
            Route::get('/{id}', [AdminNoticeController::class, 'show']);
            Route::put('/{id}', [AdminNoticeController::class, 'update']);
            Route::patch('/{id}/status', [AdminNoticeController::class, 'updateStatus']);
            Route::patch('/{id}/pin', [AdminNoticeController::class, 'updatePin']);
            Route::post('/{id}/attachments', [AdminNoticeController::class, 'addAttachments']);
            Route::delete('/{id}/attachments/{attachmentId}', [AdminNoticeController::class, 'removeAttachment']);
            Route::delete('/{id}', [AdminNoticeController::class, 'destroy']);
            Route::put('/{id}/restore', [AdminNoticeController::class, 'restore']);
        });

        Route::prefix('profile-update-requests')->group(function () {
            Route::get('/', [AdminProfileUpdateRequestController::class, 'index']);
            Route::get('/{id}', [AdminProfileUpdateRequestController::class, 'show']);
            Route::patch('/{id}/approve', [AdminProfileUpdateRequestController::class, 'approve']);
            Route::patch('/{id}/reject', [AdminProfileUpdateRequestController::class, 'reject']);
        });

    });

    // ── Module 11: Dashboard ───────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        Route::prefix('dashboard')->group(function () {
            Route::get('/',                  [DashboardController::class, 'index']);
            Route::get('/superadmin',        [DashboardController::class, 'superadmin']);
            Route::get('/admin',             [DashboardController::class, 'admin']);
            Route::get('/member',            [DashboardController::class, 'member']);
            Route::get('/stats',             [DashboardController::class, 'stats']);
            Route::get('/charts',            [DashboardController::class, 'charts']);
            Route::get('/recent-activities', [DashboardController::class, 'recentActivities']);
            Route::get('/pending-items',     [DashboardController::class, 'pendingItems']);
            Route::get('/latest-content',    [DashboardController::class, 'latestContent']);
        });

        // ── Module 11: Reports ─────────────────────────────────────────────────
        Route::prefix('reports')->group(function () {
            Route::get('/overview',                   [ReportController::class, 'overview']);
            Route::get('/membership-applications',   [ReportController::class, 'membershipApplications']);
            Route::get('/members',                   [ReportController::class, 'members']);
            Route::get('/committees',                [ReportController::class, 'committees']);
            Route::get('/committee-assignments',     [ReportController::class, 'committeeAssignments']);
            Route::get('/reporting-hierarchy',       [ReportController::class, 'reportingHierarchy']);
            Route::get('/posts',                     [ReportController::class, 'posts']);
            Route::get('/notices',                   [ReportController::class, 'notices']);
            Route::get('/profile-update-requests',   [ReportController::class, 'profileUpdateRequests']);
            Route::get('/activity-summary',          [ReportController::class, 'activitySummary']);
        });

    });

});

