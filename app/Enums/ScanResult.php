<?php

namespace App\Enums;

enum ScanResult: string
{
    case Valid = 'valid';
    case Duplicate = 'duplicate';
    case Invalid = 'invalid';
}
