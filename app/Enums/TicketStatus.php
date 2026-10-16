<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Valid = 'valid';
    case Used = 'used';
    case Voided = 'voided';
}
