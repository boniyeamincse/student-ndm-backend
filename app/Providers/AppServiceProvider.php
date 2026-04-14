<?php

namespace App\Providers;

use App\Models\Member;
use App\Models\MembershipApplication;
use App\Models\Committee;
use App\Models\CommitteeMemberAssignment;
use App\Models\CommitteeType;
use App\Models\MemberReportingRelation;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Notice;
use App\Models\ProfileUpdateRequest;
use App\Models\Position;
use App\Policies\CommitteeMemberAssignmentPolicy;
use App\Policies\CommitteePolicy;
use App\Policies\CommitteeTypePolicy;
use App\Policies\MemberReportingRelationPolicy;
use App\Policies\PostCategoryPolicy;
use App\Policies\PostPolicy;
use App\Policies\NoticePolicy;
use App\Policies\ProfileUpdateRequestPolicy;
use App\Policies\PositionPolicy;
use App\Policies\MemberPolicy;
use App\Policies\MembershipApplicationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(MembershipApplication::class, MembershipApplicationPolicy::class);
        Gate::policy(Member::class, MemberPolicy::class);
        Gate::policy(CommitteeType::class, CommitteeTypePolicy::class);
        Gate::policy(Committee::class, CommitteePolicy::class);
        Gate::policy(Position::class, PositionPolicy::class);
        Gate::policy(CommitteeMemberAssignment::class, CommitteeMemberAssignmentPolicy::class);
        Gate::policy(MemberReportingRelation::class, MemberReportingRelationPolicy::class);
        Gate::policy(PostCategory::class, PostCategoryPolicy::class);
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(Notice::class, NoticePolicy::class);
        Gate::policy(ProfileUpdateRequest::class, ProfileUpdateRequestPolicy::class);
    }
}

