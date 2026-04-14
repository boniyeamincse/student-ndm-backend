<?php

namespace App\Enum;

enum ReportingRelationHistoryAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Activated = 'activated';
    case Deactivated = 'deactivated';
    case StatusChanged = 'status_changed';
    case SuperiorChanged = 'superior_changed';
    case RelationTypeChanged = 'relation_type_changed';
    case PrimaryChanged = 'primary_changed';
    case Restored = 'restored';
    case Deleted = 'deleted';
}
