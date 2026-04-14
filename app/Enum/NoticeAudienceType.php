<?php

namespace App\Enum;

enum NoticeAudienceType: string
{
    case All = 'all';
    case CommitteeSpecific = 'committee_specific';
    case LeadershipOnly = 'leadership_only';
    case MembersOnly = 'members_only';
    case Custom = 'custom';
}
