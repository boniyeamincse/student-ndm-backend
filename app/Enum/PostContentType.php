<?php

namespace App\Enum;

enum PostContentType: string
{
    case Blog = 'blog';
    case News = 'news';
    case NoticeLike = 'notice_like';
    case Statement = 'statement';
    case EventUpdate = 'event_update';
    case PressRelease = 'press_release';
    case Other = 'other';
}
