<?php

namespace App\Enum;

enum ProfileVisibility: string
{
    case Private = 'private';
    case MembersOnly = 'members_only';
    case Public = 'public';
}
