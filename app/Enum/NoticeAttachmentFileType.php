<?php

namespace App\Enum;

enum NoticeAttachmentFileType: string
{
    case Image = 'image';
    case Pdf = 'pdf';
    case Doc = 'doc';
    case Docx = 'docx';
    case Xls = 'xls';
    case Xlsx = 'xlsx';
    case Csv = 'csv';
    case Zip = 'zip';
    case Other = 'other';
}
