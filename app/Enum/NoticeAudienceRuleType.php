<?php

namespace App\Enum;

enum NoticeAudienceRuleType: string
{
    case Role = 'role';
    case CommitteeType = 'committee_type';
    case Committee = 'committee';
    case Member = 'member';
    case Position = 'position';
    case AssignmentType = 'assignment_type';
    case Custom = 'custom';
}
