<?php

namespace App\Enums;

enum HoldStatus: string
{
    case Active = 'active';
    case Released = 'released';
    case Expired = 'expired';
    case Converted = 'converted';
}
