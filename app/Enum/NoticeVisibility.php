<?php

namespace App\Enum;

enum NoticeVisibility: string
{
    case Public = 'public';
    case MembersOnly = 'members_only';
    case Internal = 'internal';
}
