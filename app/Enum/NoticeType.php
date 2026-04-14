<?php

namespace App\Enum;

enum NoticeType: string
{
    case General = 'general';
    case Urgent = 'urgent';
    case Meeting = 'meeting';
    case Circular = 'circular';
    case Event = 'event';
    case Election = 'election';
    case Directive = 'directive';
    case Emergency = 'emergency';
    case Other = 'other';
}
