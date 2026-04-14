<?php

namespace App\Enum;

enum AssignmentHistoryAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Activated = 'activated';
    case Deactivated = 'deactivated';
    case StatusChanged = 'status_changed';
    case Transferred = 'transferred';
    case Restored = 'restored';
    case Deleted = 'deleted';
    case PositionChanged = 'position_changed';
    case PrimaryChanged = 'primary_changed';
}
