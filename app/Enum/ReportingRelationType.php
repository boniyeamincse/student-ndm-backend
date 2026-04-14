<?php

namespace App\Enum;

enum ReportingRelationType: string
{
    case DirectReport = 'direct_report';
    case FunctionalReport = 'functional_report';
    case AdvisoryReport = 'advisory_report';
    case TemporaryReport = 'temporary_report';
    case ActingReport = 'acting_report';
    case Other = 'other';
}
