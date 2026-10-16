<?php

namespace App\Enums;

enum HoldStatus: string
{
    case Active = 'active';
    case Converted = 'converted';
    case Released = 'released';
    case Expired = 'expired';
}
