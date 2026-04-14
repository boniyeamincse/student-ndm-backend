<?php

namespace App\Enum;

enum AssignmentType: string
{
    case GeneralMember = 'general_member';
    case OfficeBearer = 'office_bearer';
    case Coordinator = 'coordinator';
    case Advisor = 'advisor';
    case Observer = 'observer';
    case Other = 'other';
}
